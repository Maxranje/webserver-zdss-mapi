<?php

class Service_Page_Api_Lockcreate extends Zy_Core_Service{

    /**
     * @var Service_Data_Lock
     */
    public $service;

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $teacherId  = empty($this->request['teacher_id']) ? 0 : intval($this->request['teacher_id']);
        $times      = empty($this->request['times']) ? array() : $this->request['times'];

        // 教师信息获取
        if ($teacherId <= 0) {
            throw new Zy_Core_Exception(405, "教师不能为空");
        }

        if (empty($times)) {
            throw new Zy_Core_Exception(405, "必须选择锁定时间");
        }

        $needTimes = array();
        $needDays  = array();
        foreach ($times as $time) {
            if (empty($time['date']) || empty($time['time_range'])) {
                throw new Zy_Core_Exception(405, "时间格式错误, 存在空情况");
            }
            $time['time_range'] = explode(",", $time['time_range']);
            if (!is_array($time['time_range']) || count($time['time_range']) != 2) {
                throw new Zy_Core_Exception(405, "时间格式错误, 时间范围存在不正确情况");
            }
            $range1 = explode(":", $time['time_range'][0]);
            $range2 = explode(":", $time['time_range'][1]);
            $sts = $time['date'] + ($range1[0] * 3600) + ($range1[1] * 60);
            $ets = $time['date'] + ($range2[0] * 3600) + ($range2[1] * 60);

            $needTimes[] = array(
                'sts' => $sts,
                'ets' => $ets,
            );
            $needDays[] = strtotime(date("Ymd", $sts));
            $needDays[] = strtotime(date("Ymd", $ets)) + 86400;
        }
        
        if (empty($needTimes)) {
            throw new Zy_Core_Exception(405, "时间格式错误, 请检查");
        }

        $ret = $this->checkParamsTime($needTimes) ;
        if (!$ret) {
            throw new Zy_Core_Exception(405, "保存的时间有冲突, 请查询后在配置");
        }

        $needDays = array(
            'sts' => min($needDays),
            'ets' => max($needDays),
        );

        $serviceUser = new Service_Data_User_Profile();
        $userInfo = $serviceUser->getUserInfoByUid(intval($teacherId));
        if (empty($userInfo)) {
            throw new Zy_Core_Exception(405, "无法查到老师信息");
        }

        $this->service = new Service_Data_Lock();
        $ret = $this->checkTeacherPk($needTimes, $needDays, $teacherId);
        if (!$ret) {
            throw new Zy_Core_Exception(405, "老师时间有冲突, 请查询排课和锁定的列表信息后在配置");
        }

        $profile = [
            'needTimes'     => $needTimes,
            'teacher_id'    => $teacherId,
            "type"          => Service_Data_User_Profile::USER_TYPE_TEACHER,
        ];

        $ret = $this->service->create($profile);
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

    private function checkTeacherPk ($needTimes, $needDays, $teacherId) {
        // 排课的时间
        $conds= array(
            sprintf('start_time >= %d', $needDays['sts']),
            sprintf('end_time <= %d', $needDays['ets']),
            'teacher_id' => intval($teacherId),
            'state' => 1,
        );
        $serviceSchedule = new Service_Data_Schedule();
        $list = $serviceSchedule->getListByConds($conds);
        if ($list === false) {
            return false;
        }
        $list = empty($list) ? array() : $list;

        // 锁定的时间
        $conds= array(
            sprintf('start_time >= %d', $needDays['sts']),
            sprintf('end_time <= %d', $needDays['ets']),
            'uid' => intval($teacherId),
        );
        $locks = $this->service->getListByConds($conds);
        if ($locks === false) {
            return false;
        }
        $locks = empty($locks) ? array() : $locks;

        // 2个记录合并
        $list = array_merge($list, $locks);

        if (empty($list)) {
            return true;
        }

        foreach ($list as $item) {
            foreach ($needTimes as $t) {
                if ($t['sts'] > $item['start_time'] && $t['sts'] < $item['end_time']) {
                    return false;
                }
                if ($t['ets'] > $item['start_time'] && $t['ets'] < $item['end_time']) {
                    return false;
                }
                if ($t['sts'] < $item['start_time'] && $t['ets'] > $item['end_time']) {
                    return false;
                }
                if ($t['sts'] == $item['start_time'] || $t['ets'] == $item['end_time']) {
                    return false;
                }
            }
        }
        return true;
    }
}