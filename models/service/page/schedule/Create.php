<?php

// 周期
class Service_Page_Schedule_Create extends Zy_Core_Service{

    public $serviceSchedule;

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }
        
        $groupId    = empty($this->request['group_id']) ? 0 : intval($this->request['group_id']);
        $teacherId  = empty($this->request['teacher_id']) ? "" : $this->request['teacher_id'];
        $areaId     = empty($this->request['area_id']) ? 0 : intval($this->request['area_id']);

        // 教师信息获取
        if (empty($teacherId) || strpos($teacherId, "_") === false) {
            throw new Zy_Core_Exception(405, "教师不能为空");
        }
        list($subjectId, $teacherId) = explode("_", $teacherId);
        if ($groupId <= 0 || $teacherId <= 0 || $subjectId <= 0){
            throw new Zy_Core_Exception(405, "教师和班级不能为空");
        }
        
        // 时间获取
        $times = array();
        foreach ($this->request as $k => $v) {
            strpos($k, "times") !== false && $times = $v;
        }

        // 时间没有, 则认为默认提交
        if (empty($times)) {
            $timeList = new Service_Data_Timelist();
            $times = $timeList->create($this->request);
            if (empty($times)) {
                throw new Zy_Core_Exception(405, "请求参数错误");
            }
        }

        $needTimes = array();
        $needDays  = array();
        foreach ($times as $time) {
            if (empty($time['date']) || empty($time['time_range'])) {
                throw new Zy_Core_Exception(405, "时间格式错误, 存在空情况");
            }
            $range = explode(",", $time['time_range']);
            if (!is_array($range) || count($range) != 2) {
                throw new Zy_Core_Exception(405, "时间格式错误, 存在空情况");
            }
            $sts = explode(":", $range[0]);
            $ets = explode(":", $range[1]);

            $start = $time['date'] + ($sts[0] * 3600) + ($sts[1] * 60);
            $end = $time['date'] + ($ets[0] * 3600) + ($ets[1] * 60);

            $needTimes[] = array(
                'sts' => $start,
                'ets' => $end,
            );
            
            $needDays[] = strtotime(date("Ymd", $start));
            $needDays[] = strtotime(date("Ymd", $end)) + 86400;
        }

        if (empty($needTimes)) {
            throw new Zy_Core_Exception(405, "时间格式错误, 请检查");
        }

        $ret = $this->checkParamsTime($needTimes) ;
        if (!$ret) {
            throw new Zy_Core_Exception(405, "保存的时间有冲突, 请查询后在配置");
        }

        $serviceUser = new Service_Data_User_Profile();
        $userInfo = $serviceUser->getUserInfoByUid(intval($teacherId));
        if (empty($userInfo)) {
            throw new Zy_Core_Exception(405, "无法查到老师信息");
        }
        
        $serviceGroup = new Service_Data_Group();
        $groupInfos = $serviceGroup->getGroupById($groupId);
        if (empty($groupInfos)) {
            throw new Zy_Core_Exception(405, "无法查到班级信息");
        }
        $areaop = intval($groupInfos['area_op']);

        $serviceColumn = new Service_Data_Column();
        $columnInfos = $serviceColumn->getColumnByTSId($teacherId, $subjectId);
        if (empty($columnInfos)) {
            throw new Zy_Core_Exception(405, "无法查到教师绑定信息");
        }

        if ($areaId > 0) {
            $serviceArea = new Service_Data_Area();
            $areaInfo = $serviceArea->getAreaById($areaId, false);
            if (empty($areaInfo)) {
                throw new Zy_Core_Exception(405, "无法查到校区信息");
            }
        }

        $needDays = array(
            "sts" => min($needDays),
            "ets" => max($needDays),
        );
        $this->serviceSchedule = new Service_Data_Schedule();

        $ret = $this->serviceSchedule->checkGroup ($needTimes, $needDays, $groupId);
        if ($ret === false) {
            throw new Zy_Core_Exception(405, "查询班级排课冲突情况失败, 请重新提交");
        }
        if (!empty($ret)) {
            $jobIds = implode(", ", array_column($ret, 'id'));
            throw new Zy_Core_Exception(406, "班级时间有冲突, 请检查班级时间, 排课编号分别为" . $jobIds. " 仅做参考");
        }

        $ret = $this->serviceSchedule->checkTeacherPk($needTimes, $needDays, $teacherId);
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

        $profile = [
            "column_id" => $columnInfos['id'],
            'group_id' => $groupInfos['id'],
            'area_op'  => $areaop,
            'needTimes' => $needTimes,
            'teacher_id' => $teacherId,
            'area_id' => $areaId,
            'state'   => 1,
        ];

        $ret = $this->serviceSchedule->create($profile);
        if ($ret === false) {
            throw new Zy_Core_Exception(405, "添加失败, 请重试");
        }
        return array();
    }

    private function checkParamsTime ($needTimes) {
        $times = $needTimes;
        foreach ($times as $k1 => $item) {
            foreach ($needTimes as $k2 => $t) {
                // 比较, 开始时间大于存开始时间,  结束时间小于存结束时间
                if ($k1 == $k2) {
                    continue;
                }
                if ($t['sts'] > $item['sts'] && $t['sts'] < $item['ets']) {
                    return false;
                }
                if ($t['ets'] > $item['sts'] && $t['ets'] < $item['ets']) {
                    return false;
                }
                if ($t['sts'] < $item['sts'] && $t['ets'] > $item['ets']) {
                    return false;
                }
                if ($t['sts'] == $item['sts'] || $t['ets'] == $item['ets']) {
                    return false;
                }
            }
        }
        return true;
    }
}