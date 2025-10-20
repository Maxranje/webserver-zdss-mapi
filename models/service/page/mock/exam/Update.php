<?php

class Service_Page_Mock_Exam_Update extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $exam        = empty($this->request['exam']) ? array() : $this->request['exam'];
        $examId      = empty($this->request['exam_id']) ? 0 : intval($this->request['exam_id']);
        $expireTime  = empty($this->request['expire_time']) ? 0 : intval($this->request['expire_time']);
        $pid         = empty($this->request['pid']) ? "" : trim($this->request['pid']);
        $startEnd    = empty($this->request['start_end']) ? "" : trim($this->request['start_end']);
        $remark      = empty($this->request['remark']) ? "" : trim($this->request['remark']);
        $studentUids = empty($this->request['student_uids']) ? array() : $this->request["student_uids"];
        $teacherUid  = empty($this->request['teacher_uid']) ? 0 : intval($this->request['teacher_uid']);

        $pidArr = explode("_", $pid);
        if (!is_array($pidArr) || count($pidArr) != 2 || intval($pidArr[0]) <= 0 || !in_array($pidArr[1], Service_Data_Paper::PAPER_TYPE_MAP)) {
            throw new Zy_Core_Exception(405, "操作失败, 必须选定试卷或所选试卷异常");
        }
        $pid = intval($pidArr[0]);
        $paperType = intval($pidArr[1]);

        $examId = empty($exam["exam_id"]) ? 0 : intval($exam["exam_id"]);
        if ($examId <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 未选定要求改的考试");
        }

        if (is_array($studentUids)) {
            $studentUids = Zy_Helper_Utils::arrayInt($studentUids);
        } else if (is_string($studentUids)) {
            $studentUids = Zy_Helper_Utils::arrayInt(explode(",", $this->request['student_uids']));
        }

        $startEnd = Zy_Helper_Utils::arrayInt(explode(",", $startEnd));
        if (count($startEnd) != 2 || $startEnd[0] <=0 || $startEnd[1] <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 日期时间范围选定不正确");
        }
        $startTime  = $startEnd[0];
        $endTime    = $startEnd[1];
        if ($endTime <= $startTime ) {
            throw new Zy_Core_Exception(405, "操作失败, 日期时间范围选定不正确!");
        }

        if ($expireTime < 10 || $expireTime > 240) {
            throw new Zy_Core_Exception(405, "操作失败, 时长必须要在10分钟到240分钟之间");
        }

        if ($teacherUid <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 必须选定监考老师");
        }

        if (empty($studentUids) || count($studentUids) > 50) {
            throw new Zy_Core_Exception(405, "操作失败, 必须选择考生并且单次考试50人内");
        }

        if (!empty($remark) && !Zy_Helper_Utils::validateString($remark, 1, 100)) {
            throw new Zy_Core_Exception(405, "操作失败, 备注限定100字内");
        }

        $serviceData = new Service_Data_Paper();
        $paper = $serviceData->getPaperById($pid);
        if (empty($paper)) {
            throw new Zy_Core_Exception(405, "操作失败, 试卷不存在或已被删除");
        }
        if ($paper["type"] != $paperType) {
            throw new Zy_Core_Exception(405, "操作失败, 试卷类型与请求参数不符合, 请刷新重试");
        }

        // 根据实际题数, 填充到exam中
        $paperQids = $serviceData->getPaperQuestionIds($pid);
        if (count($paperQids) <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 试卷没有关联试题.");
        }
        if ($paper["type"] == Service_Data_Paper::PAPER_TYPE_NORMAL) {
            $totalQuestion = count($paperQids);
        } else {
            $totalQuestion = Service_Data_Paper::OUTPUT_ASSESS_TOTAL_QUESTION;
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
                throw new Zy_Core_Exception(405, sprintf("操作失败, 考生uid:%d, 无法参加考试(不存在或下线或不是模考考生, 请检查", $v));
            }
        }

        $serviceData = new Service_Data_Exam();
        $profile = [
            "start_time"        => $startTime,
            "end_time"          => $endTime,
            "expire_time"       => $expireTime,
            "pid"               => $pid,
            "total_question"    => $totalQuestion,
            "student_uids"      => $studentUids,
            "teacher_uid"       => $teacherUid,
            "remark"            => $remark,
            'paper'             => $paper,
        ];

        $ret = $serviceData->update($examId, $profile);
        if ($ret == false) {
            throw new Zy_Core_Exception(405, "配置考试失败, 请重试");
        }
        return array();
    }
}