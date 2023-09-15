<?php

class Service_Page_Schedule_Update extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin() || !$this->isModeAble(Service_Data_Roles::ROLE_MODE_SCHEDULE_UPDATE)) {
            throw new Zy_Core_Exception(405, "无权限操作");
        }

        $id             = empty($this->request['id']) ? 0 : intval($this->request['id']);
        $date           = empty($this->request['date']) ? 0 : intval($this->request['date']);
        $timeRange      = empty($this->request['time_range']) ? "" : $this->request['time_range'];
        $teacherUid     = empty($this->request['teacher_uid']) ? "" : $this->request['teacher_uid'];
        $areaOperator   = empty($this->request['area_opreator']) ? 0 : intval($this->request['area_opreator']);

        if ($id <= 0){
            throw new Zy_Core_Exception(405, "请求参数错误");
        }

        // 教师信息获取
        if (empty($teacherUid) || strpos($teacherUid, "_") === false) {
            throw new Zy_Core_Exception(405, "操作失败, 教师信息不能为空");
        }
        list($subjectId, $teacherUid) = explode("_", $teacherUid);
        if ($subjectId <= 0 || $teacherUid <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 教师信息不能为空");
        }

        $serviceSchedule = new Service_Data_Schedule();
        $info = $serviceSchedule->getScheduleById($id);
        if (empty($info) || $info['state'] != Service_Data_Schedule::SCHEDULE_ABLE) {
            throw new Zy_Core_Exception(405, "操作失败, 订单已不存在或已结束");
        }

        if ($date <= 0){
            throw new Zy_Core_Exception(405, "操作失败, 调整日期格式不正确");
        }

        $timeRange = empty($timeRange) ? array() : explode(",", $timeRange);
        if (empty($timeRange) || count($timeRange) != 2) {
            throw new Zy_Core_Exception(405, "操作失败, 时间范围有问题");
        }

        $needTimes = array();
        foreach ($timeRange as $item) {
            $range = explode(":", $item);
            if (empty($range) || count($range) != 2) {
                throw new Zy_Core_Exception(405, "操作失败, 时间必须都要配置并且时间格式不能有错");
            }
            $needTimes[] = $date + ($range[0] * 3600) + ($range[1] * 60);
        }

        if (empty($needTimes)) {
            throw new Zy_Core_Exception(405, "操作失败, 时间不正确, 请检查");
        }

        $needTimes = array(
            'sts' => min($needTimes),
            'ets' => max($needTimes),
        );

        // 5分钟到4小时
        if ($needTimes['sts'] >= $needTimes['ets'] 
            || $needTimes['ets'] - $needTimes['sts'] > (4 * 3600)
            || $needTimes['ets'] - $needTimes['sts'] < 300) {
            throw new Zy_Core_Exception(405, "操作失败, 模板时间必须在5分钟到4小时之间");
        }

        $needDays = array(
            'sts' => strtotime(date('Ymd', $needTimes['sts'])),
            'ets' => strtotime(date('Ymd', $needTimes['ets'] + 86400)),
        );

        $serviceUser = new Service_Data_Profile();
        $userInfo = $serviceUser->getUserInfoByUid($teacherUid);
        if (empty($userInfo) || $userInfo['state'] != Service_Data_Profile::STUDENT_ABLE) {
            throw new Zy_Core_Exception(405, "操作失败, 无法查到老师信息或教师已下线");
        }

        $serviceColumn = new Service_Data_Column();
        $columnInfos = $serviceColumn->getColumnByTSId($teacherUid, $subjectId);
        if (empty($columnInfos)) {
            throw new Zy_Core_Exception(405, "操作失败, 无法查到教师绑定信息");
        }

        $ret = $serviceSchedule->checkTeacherPk(array($needTimes), $needDays, $teacherUid, $info);
        if ($ret === false) {
            throw new Zy_Core_Exception(405, "操作失败, 查询教师排课冲突情况失败, 请重新提交");
        }
        if (!empty($ret['schedule']) || !empty($ret['lock'])) {
            $jobIds = implode(", ", array_column($ret['schedule'], 'id'));
            $lockIds = implode(", ", array_column($ret['lock'], 'id'));
            $msg = "操作失败, 教师时间有冲突, 请检查教师时间或教师锁定时间";
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
            throw new Zy_Core_Exception(405, "操作失败, 无法查到班级信息");
        }

        $ret = $serviceSchedule->checkGroup (array($needTimes), $needDays, $groupInfos['id'], $info);
        if ($ret === false) {
            throw new Zy_Core_Exception(405, "操作失败, 查询班级排课冲突情况失败, 请重新提交");
        }
        if (!empty($ret)) {
            $jobIds = implode(", ", array_column($ret, 'id'));
            throw new Zy_Core_Exception(406, "操作失败, 班级时间有冲突, 请检查班级时间, 排课编号分别为" . $jobIds. " 仅做参考");
        }

        // 排查教室 (3.15线上不管)
        if (!empty($info['area_id']) && !empty($info['room_id'])) {
            $serviceData = new Service_Data_Area();
            $areaInfo = $serviceData->getAreaById(intval($info['area_id']));
            if (empty($areaInfo['is_online'])) {
                $ret = $serviceSchedule->checkRoom (array($needTimes), $needDays, $info);
                if ($ret === false) {
                    throw new Zy_Core_Exception(405, "操作失败, 查询教室冲突情况失败, 请重新提交");
                }
                if (!empty($ret)) {
                    $jobIds = implode(", ", array_column($ret, 'id'));
                    throw new Zy_Core_Exception(406, "操作失败, 教室占用时间有冲突, 请检查教室占用情况, 排课编号分别为" . $jobIds. " 仅做参考");
                }
            }
        }

        $profile = [
            "id"            => $id,
            "column_id"     => $columnInfos['id'],
	        'needTimes'     => $needTimes,
            "area_op"       => $areaOperator,
	        'teacher_uid'   => $teacherUid,
            'subject_id'    => $subjectId,
        ];

        $ret = $serviceSchedule->update($profile);
        if ($ret === false) {
            throw new Zy_Core_Exception(405, "更新失败, 请重试");
        }
        return array();
    }
}
