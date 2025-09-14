<?php

class Service_Page_Mock_Exam_Start extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $expireTime  = empty($this->request['expire_time']) ? 0 : intval($this->request['expire_time']);
        $pid         = empty($this->request['pid']) ? 0 : intval($this->request['pid']);
        $startEnd    = empty($this->request['start_end']) ? "" : trim($this->request['start_end']);
        $remark      = empty($this->request['remark']) ? "" : trim($this->request['remark']);
        $studentUids = empty($this->request['student_uids']) ? array() : Zy_Helper_Utils::arrayInt(explode(",", $this->request['student_uids']));
        $teacherUid  = empty($this->request['teacher_uid']) ? 0 : intval($this->request['teacher_uid']);

        $startEnd = Zy_Helper_Utils::arrayInt(explode(",", $startEnd));
        if (count($startEnd) != 2 || $startEnd[0] <=0 || $startEnd[1] <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 日期时间范围选定不正确");
        }
        $startTime  = $startEnd[0];
        $endTime    = $startEnd[1];
        if ($endTime <= $startTime ) {
            throw new Zy_Core_Exception(405, "操作失败, 日期时间范围选定不正确!");
        }

        if ($pid <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 必须选定试卷");
        }

        if ($expireTime < 10 || $expireTime > 240) {
            throw new Zy_Core_Exception(405, "操作失败, 时长必须要在10分钟到240分钟之间");
        }

        if ($teacherUid <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 必须选定监考老师");
        }

        if (empty($studentUids) || count($studentUids) > 50) {
            throw new Zy_Core_Exception(405, "操作失败, 必须选择学员并且单次考试50人内");
        }

        if (!empty($remark) && !Zy_Helper_Utils::validateString($remark, 1, 100)) {
            throw new Zy_Core_Exception(405, "操作失败, 备注限定100字内");
        }

        $serviceData = new Service_Data_Paper();
        $paper = $serviceData->getPaperById($pid);
        if (empty($paper)) {
            throw new Zy_Core_Exception(405, "操作失败, 试卷不存在或已被删除");
        }
        
        $uids = array_merge($studentUids, array($teacherUid));
        $serviceData = new Service_Data_Profile();
        $userInfos = $serviceData->getUserInfoByUids($uids);
        $userInfos = array_column($userInfos, null, "uid");
        if (empty($userInfos[$teacherUid]) || $userInfos[$teacherUid]["state"] == Service_Data_Profile::STUDENT_DISABLE) {
            throw new Zy_Core_Exception(405, "操作失败, 监考老师不存在或已下线");
        }
        foreach ($uids as $v) {
            if (empty($userInfos[$v]) || 
                $userInfos[$v]["state"] == Service_Data_Profile::STUDENT_DISABLE || 
                ($userInfos[$v]["type"] == Service_Data_Profile::USER_TYPE_STUDENT && empty($userInfos[$v]["is_mock"]))) {
                throw new Zy_Core_Exception(405, sprintf("操作失败, 学员uid:%d, 无法参加考试(不存在或下线或不是模考学员, 请检查", $v));
            }
        }

        $serviceData = new Service_Data_Exam();
        $profile = [
            "indentify"         => Zy_Helper_Utils::autoID("EX"),
            "start_time"        => $startTime,
            "end_time"          => $endTime,
            "expire_time"       => $expireTime,
            "pid"               => $pid,
            "student_uids"      => $studentUids,
            "teacher_uid"       => $teacherUid,
            "remark"            => $remark,
            'paper'             => $paper,
        ];

        $ret = $serviceData->create($profile);
        if ($ret == false) {
            throw new Zy_Core_Exception(405, "配置考试失败, 请重试");
        }
        return array();
    }
}