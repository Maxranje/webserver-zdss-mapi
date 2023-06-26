<?php

class Service_Page_Schedule_Update extends Zy_Core_Service{

    public $serviceSchedule;

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $id         = empty($this->request['id']) ? 0 : intval($this->request['id']);
        $date       = empty($this->request['date']) ? 0 : intval($this->request['date']);
        $timeRange  = empty($this->request['time_range']) ? "" : $this->request['time_range'];
        $areaop     = empty($this->request['area_op']) ? 0 : intval($this->request['area_op']);

        if ($id <= 0){
            throw new Zy_Core_Exception(405, "请求参数错误");
        }

        $this->serviceSchedule = new Service_Data_Schedule();
        $info = $this->serviceSchedule->getScheduleById($id);
        if (empty($info) || $info['state'] != 1) {
            throw new Zy_Core_Exception(405, "订单已不存在或已结束");
        }

        $subjectId = $teacherId = 0;
        if (strpos($this->request['teacherId'], "_") !== false) {
            list($subjectId, $teacherId) = explode("_", $this->request['teacherId']);
            $subjectId = intval($subjectId);
            $teacherId = intval($teacherId);
        }

        if ($date <= 0){
            throw new Zy_Core_Exception(405, "调整日期格式不正确");
        }

        $timeRange = empty($timeRange) ? array() : explode(",", $timeRange);
        if (empty($timeRange) || count($timeRange) != 2) {
            throw new Zy_Core_Exception(405, "时间范围有问题");
        }

        $needTimes = array();
        foreach ($timeRange as $item) {
            $range = explode(":", $item);
            if (empty($range) || count($range) != 2) {
                throw new Zy_Core_Exception(405, "时间必须都要配置并且时间格式不能有错");
            }
            $needTimes[] = $date + ($range[0] * 3600) + ($range[1] * 60);
        }

        if (empty($needTimes)) {
            throw new Zy_Core_Exception(405, "时间不正确, 请检查");
        }

        $needTimes = array(
            'sts' => min($needTimes),
            'ets' => max($needTimes),
        );

        // 5分钟到4小时
        if ($needTimes['sts'] >= $needTimes['ets'] 
            || $needTimes['ets'] - $needTimes['sts'] > (4 * 3600)
            || $needTimes['ets'] - $needTimes['sts'] < 300) {
            throw new Zy_Core_Exception(405, "模板时间必须在5分钟到4小时之间");
        }

        $needDays = array(
            'sts' => strtotime(date('Ymd', $needTimes['sts'])),
            'ets' => strtotime(date('Ymd', $needTimes['ets'] + 86400)),
        );

        if ($subjectId > 0 && $teacherId > 0) {
            $serviceUser = new Service_Data_User_Profile();
            $userInfo = $serviceUser->getUserInfoByUid($teacherId);
            if (empty($userInfo)) {
                throw new Zy_Core_Exception(405, "无法查到老师信息");
            }
    
            $serviceColumn = new Service_Data_Column();
            $columnInfos = $serviceColumn->getColumnByTSId($teacherId, $subjectId);
            if (empty($columnInfos)) {
                throw new Zy_Core_Exception(405, "无法查到教师绑定信息");
            }

        } else {
            $serviceColumn = new Service_Data_Column();
            $columnInfos = $serviceColumn->getColumnById(intval($info['column_id']));
            if (empty($columnInfos)) {
                throw new Zy_Core_Exception(405, "无法查到教师绑定信息");
            }

        }

        $ret = $this->serviceSchedule->checkTeacherPk(array($needTimes), $needDays, $columnInfos['teacher_id'], $info);
        if ($ret === false) {
            throw new Zy_Core_Exception(405, "查询教师排课冲突情况失败, 请重新提交");
        }
        if (!empty($ret['schedule']) || !empty($ret['lock'])) {
            $jobIds = implode(", ", array_column($ret['schedule'], 'id'));
            $lockIds = implode(", ", array_column($ret['lock'], 'id'));
            $msg = "教师时间有冲突, 请检查教师时间或教师锁定时间";
            if (!empty($jobIds)) {
                $msg.= ", 排课编号分别是: " . $jobIds;
            }
            if (!empty($lockIds)) {
                $msg.= ", 锁定编号分别是: " . $lockIds;
            }
            $msg .= ", 仅供参考";
            throw new Zy_Core_Exception(405, $msg);
        }
        
        $serviceGroup = new Service_Data_Group();
        $groupInfos = $serviceGroup->getGroupById($info['group_id']);
        if (empty($groupInfos)) {
            throw new Zy_Core_Exception(405, "无法查到班级信息");
        }

        $ret = $this->serviceSchedule->checkGroup (array($needTimes), $needDays, $groupInfos['id'], $info);
        if ($ret === false) {
            throw new Zy_Core_Exception(405, "查询班级排课冲突情况失败, 请重新提交");
        }
        if (!empty($ret)) {
            $jobIds = implode(", ", array_column($ret, 'id'));
            throw new Zy_Core_Exception(406, "班级时间有冲突, 请检查班级时间, 排课编号分别为" . $jobIds. " 仅做参考");
        }

        // 排查教室 (3.15线上不管)
        if (!empty($info['area_id']) && !empty($info['room_id']) && $info['area_id'] != 3 && $info['room_id'] != 15) {
            $ret = $this->serviceSchedule->checkRoom (array($needTimes), $needDays, $info);
            if ($ret === false) {
                throw new Zy_Core_Exception(405, "查询教室冲突情况失败, 请重新提交");
            }
            if (!empty($ret)) {
                $jobIds = implode(", ", array_column($ret, 'id'));
                throw new Zy_Core_Exception(406, "教室占用时间有冲突, 请检查教室占用情况, 排课编号分别为" . $jobIds. " 仅做参考");
            }
        }

        $profile = [
            "id" => $id,
            "column_id" => $columnInfos['id'],
	        'needTimes' => $needTimes,
            "area_op" => $areaop,
	        'teacher_id' => $columnInfos['teacher_id'],
        ];

        $ret = $this->serviceSchedule->update($profile);
        if ($ret === false) {
            throw new Zy_Core_Exception(405, "更新失败, 请重试");
        }
        return array();
    }
}
