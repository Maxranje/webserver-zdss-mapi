<?php

class Service_Page_Teacher_Lock_Create extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $teacherUid     = empty($this->request['teacher_uid']) ? 0 : intval($this->request['teacher_uid']);
        $times          = empty($this->request['times']) ? array() : $this->request['times'];

        // 教师信息获取
        if ($teacherUid <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 教师不能为空");
        }

        if (empty($times)) {
            throw new Zy_Core_Exception(405, "操作失败, 必须选择锁定时间");
        }

        $serviceUser = new Service_Data_Profile();
        $userInfo = $serviceUser->getUserInfoByUid(intval($teacherUid));
        if (empty($userInfo) || $userInfo['state'] == Service_Data_Profile::STUDENT_DISABLE) {
            throw new Zy_Core_Exception(405, "操作失败, 无法查到老师信息, 或教师已下线");
        }

        $needTimes = array();
        $needDays  = array();
        foreach ($times as $time) {
            if (empty($time['date']) || empty($time['time_range'])) {
                throw new Zy_Core_Exception(405, "操作失败, 时间格式错误, 存在空情况");
            }
            $time['time_range'] = explode(",", $time['time_range']);
            if (!is_array($time['time_range']) || count($time['time_range']) != 2) {
                throw new Zy_Core_Exception(405, "操作失败, 时间格式错误, 时间范围存在不正确情况");
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
            throw new Zy_Core_Exception(405, "操作失败, 时间格式错误, 请检查");
        }

        $servicData = new Service_Data_Schedule();
        $ret = $servicData->checkParamsTime($needTimes) ;
        if (!$ret) {
            throw new Zy_Core_Exception(405, "操作失败, 保存的时间有冲突, 请查询后在配置");
        }

        $needDays = array(
            'sts' => min($needDays),
            'ets' => max($needDays),
        );

        $ret = $servicData->checkTeacherPk($needTimes, $needDays, $teacherUid);
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

        $profile = [
            'need_times'   => $needTimes,
            'teacher_uid'   => $teacherUid,
        ];

        $servicData = new Service_Data_Lock();
        $ret = $servicData->create($profile);
        if ($ret === false) {
            throw new Zy_Core_Exception(405, "添加失败, 请重试");
        }
        return array();
    }


}