<?php

// 周期
class Service_Page_Schedule_Create extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }
        
        $teacherUid  = empty($this->request['teacher_uid']) ? "" : $this->request['teacher_uid'];
        $groupId     = empty($this->request['group_id']) ? 0 : intval($this->request['group_id']);
        $areaId      = empty($this->request['area_id']) ? 0 : intval($this->request['area_id']);

        // 教师信息获取
        if (empty($teacherUid) || strpos($teacherUid, "_") === false) {
            throw new Zy_Core_Exception(405, "操作失败, 教师信息不能为空");
        }

        list($subjectId, $teacherUid) = explode("_", $teacherUid);
        if ($groupId <= 0 || $teacherUid <= 0 || $subjectId <= 0){
            throw new Zy_Core_Exception(405, "操作失败, 教师和班级不能为空");
        }
        
        // 时间获取
        $times = array();
        foreach ($this->request as $k => $v) {
            strpos($k, "times") !== false && $times = $v;
        }

        // 时间没有, 则认为默认提交
        if (empty($times)) {
            $this->request['is_simple'] = true;
            $timeService = new Service_Page_Schedule_Timelist($this->request, $this->adption);
            $times = $timeService->execute();
            if (empty($times)) {
                throw new Zy_Core_Exception(405, "操作失败, 格式化时间参数错误");
            }
        }

        $needTimes = array();
        $needDays  = array();
        foreach ($times as $time) {
            if (empty($time['date']) || empty($time['start_time']) || empty($time["end_time"])) {
                throw new Zy_Core_Exception(405, "操作失败, 时间格式错误, 存在空情况");
            }
            $sts = explode(":", $time["start_time"]);
            $ets = explode(":", $time['end_time']);
            if (count($sts) != 2 || count($ets) != 2) {
                throw new Zy_Core_Exception(405, "操作失败, 时间格式错误, 存在异常配置");
            }

            $start = $time['date'] + ($sts[0] * 3600) + ($sts[1] * 60);
            $end = $time['date'] + ($ets[0] * 3600) + ($ets[1] * 60);

            // 5分钟到4小时
            if ($start >= $end || $end - $start > (4*3600) || $end - $start < 300) {
                throw new Zy_Core_Exception(405, "操作失败, 排课时间列表中存在配置时间异常, 开始时间>=结束时间, 时间周期<5分钟或大于4小时");
            }

            $needTimes[] = array(
                'sts' => $start,
                'ets' => $end,
            );
            
            $needDays[] = strtotime(date("Ymd", $start));
            $needDays[] = strtotime(date("Ymd", $end)) + 86400;
        }

        if (empty($needTimes)) {
            throw new Zy_Core_Exception(405, "操作失败, 时间格式错误, 请检查");
        }

        $serviceData = new Service_Data_Column();
        $columnInfos = $serviceData->getColumnByTSId(intval($teacherUid), intval($subjectId));
        if (empty($columnInfos)) {
            throw new Zy_Core_Exception(405, "操作失败, 无法查到教师绑定信息");
        }

        $serviceData = new Service_Data_Subject();
        $subjectInfo = $serviceData->getSubjectById(intval($subjectId));
        if (empty($subjectInfo) || empty($subjectInfo['parent_id'])) {
            throw new Zy_Core_Exception(405, "操作失败, 没有科目信息, 或绑定不是科目单项, 无法排课");
        }

        $serviceData = new Service_Data_Group();
        $groupInfo = $serviceData->getGroupById($groupId);
        if (empty($groupInfo) || $groupInfo['state'] == Service_Data_Group::GROUP_DISABLE) {
            throw new Zy_Core_Exception(405, "操作失败, 无法查到班级信息或班级下线");
        }

        if ($subjectInfo['parent_id'] != $groupInfo['subject_id']) {
            throw new Zy_Core_Exception(405, "操作失败, 班级所绑定的科目与老师绑定的科目不同, 无法创建课程");
        }

        $serviceSchedule = new Service_Data_Schedule();
        $ret = $serviceSchedule->checkParamsTime($needTimes) ;
        if (!$ret) {
            throw new Zy_Core_Exception(405, "操作失败, 保存的时间有冲突, 请查询后在配置");
        }

        $serviceData = new Service_Data_Profile();
        $userInfo = $serviceData->getUserInfoByUid($teacherUid);
        if (empty($userInfo) || $userInfo['state'] == Service_Data_Profile::STUDENT_DISABLE) {
            throw new Zy_Core_Exception(405, "操作失败, 无法查到老师信息或教师下线");
        }

        if ($areaId > 0) {
            $serviceData = new Service_Data_Area();
            $areaInfo = $serviceData->getAreaById($areaId, false);
            if (empty($areaInfo)) {
                throw new Zy_Core_Exception(405, "操作失败, 无法查到校区信息");
            }
        }

        $needDays = array(
            "sts" => min($needDays),
            "ets" => max($needDays),
        );

        $ret = $serviceSchedule->checkGroup ($needTimes, $needDays, $groupId);
        if ($ret === false) {
            throw new Zy_Core_Exception(405, "操作失败, 查询班级排课冲突情况失败, 请重新提交");
        }
        if (!empty($ret)) {
            $jobIds = implode(", ", array_column($ret, 'id'));
            throw new Zy_Core_Exception(406, "操作失败, 班级时间有冲突, 请检查班级时间, 排课编号分别为" . $jobIds. " 仅做参考");
        }

        $ret = $serviceSchedule->checkTeacherPk($needTimes, $needDays, $teacherUid);
        if ($ret === false) {
            throw new Zy_Core_Exception(405, "操作失败, 查询教师排课冲突情况失败, 请重新提交");
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
            "column_id"     => intval($columnInfos['id']),
            'group_id'      => intval($groupInfo['id']),
            'subject_id'    => intval($subjectId),
            'area_operator' => intval($groupInfo['area_operator']),
            'needTimes'     => $needTimes,
            'teacher_uid'   => intval($teacherUid),
            'area_id'       => $areaId,
        ];

        $ret = $serviceSchedule->create($profile);
        if ($ret === false) {
            throw new Zy_Core_Exception(405, "添加失败, 请重试");
        }
        return array();
    }
}