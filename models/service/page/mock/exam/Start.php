<?php

class Service_Page_Mock_Exam_Start extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }
        $indentify   = empty($this->request['exam_indentify']) ? "" : trim($this->request['exam_indentify']);
        $examId      = empty($this->request['exam_id']) ? 0 : intval($this->request['exam_id']);
        $expireTime  = empty($this->request['expire_time']) ? 0 : intval($this->request['expire_time']);
        $pid         = empty($this->request['pid']) ? "" : trim($this->request['pid']);
        $startEnd    = empty($this->request['start_end']) ? "" : trim($this->request['start_end']);
        $remark      = empty($this->request['remark']) ? "" : trim($this->request['remark']);
        $studentUids = empty($this->request['student_uids']) ? array() : $this->request["student_uids"];
        $teacherUid  = empty($this->request['teacher_uid']) ? 0 : intval($this->request['teacher_uid']);

        if ($expireTime < 10 || $expireTime > 240) {
            throw new Zy_Core_Exception(405, "操作失败, 时长必须要在10分钟到240分钟之间");
        }        

        $startEnd = Zy_Helper_Utils::arrayInt(explode(",", $startEnd));
        if (count($startEnd) != 2 || $startEnd[0] <=0 || $startEnd[1] <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 日期时间范围选定不正确");
        }
        $startTime  = $startEnd[0];
        $endTime    = $startEnd[1];
        if ($endTime <= $startTime ) {
            throw new Zy_Core_Exception(405, "操作失败, 允许考试周期时间范围选定不正确!");
        }
        if ($endTime - $startTime > 7*86400) {
            throw new Zy_Core_Exception(405, "操作失败, 允许考试周期时间范围必须7天以内");
        }
        if ($endTime - $startTime <= $expireTime * 60) {
            throw new Zy_Core_Exception(405, "操作失败, 允许考试周期时间范围必须大于考试时长");
        }
        
        $pidArr = explode("_", $pid);
        if (!is_array($pidArr) || count($pidArr) != 2 || intval($pidArr[0]) <= 0 || !in_array($pidArr[1], Service_Data_Paper::PAPER_TYPE_MAP)) {
            throw new Zy_Core_Exception(405, "操作失败, 必须选定试卷或所选试卷异常");
        }
        $pid = intval($pidArr[0]);
        $paperType = intval($pidArr[1]);

        if (is_array($studentUids)) {
            $studentUids = Zy_Helper_Utils::arrayInt($studentUids);
        } else if (is_string($studentUids)) {
            $studentUids = Zy_Helper_Utils::arrayInt(explode(",", $this->request['student_uids']));
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

        // get exam info
        $historyPid = $pid;
        $serviceExam = new Service_Data_Exam();
        if (!empty($indentify)) {
            $examInfo = $serviceExam->getExamByIndentify($indentify);
            if ($examId != $examInfo["id"]) {
                throw new Zy_Core_Exception(405, "操作失败, 模考标识与模考信息不匹配");
            }
            if ($examInfo["status"] != Service_Data_Exam::EXAM_STATUS_PENDING) {
                throw new Zy_Core_Exception(405, "操作失败, 模考已经进行中不允许修改");
            }
            $historyPid = intval($examInfo["pid"]);
        }

        // get paper info
        $serviceData = new Service_Data_Paper();
        $paper = $serviceData->getPaperById($pid);
        if (empty($paper)) {
            throw new Zy_Core_Exception(405, "操作失败, 试卷不存在或已被删除");
        }
        if ($paper["type"] != $paperType) {
            throw new Zy_Core_Exception(405, "操作失败, 试卷类型与请求参数不符合, 请刷新重试");
        }

        // get paper questions
        $paperQids = $serviceData->getPaperQuestionIds($pid);
        if (count($paperQids) <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 试卷没有关联试题.");
        }
        if (count($paperQids) < 10 && $paper["type"] == Service_Data_Paper::PAPER_TYPE_ASSESS) {
            throw new Zy_Core_Exception(405, "操作失败, 本次模考关联是评估类型试卷, 该类型试卷最少要有10道题才允许进行考试");
        }    
        if ($paper["type"] == Service_Data_Paper::PAPER_TYPE_NORMAL) {
            $totalQuestion = count($paperQids);
        } else {
            $totalQuestion = Service_Data_Paper::OUTPUT_ASSESS_TOTAL_QUESTION;
        }
        $paper["historyPid"] = $historyPid;
        
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

        // param
        $profile = [
            "indentify"         => empty($indentify) ? Zy_Helper_Utils::autoID("EX") : $indentify,
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

        if (!empty($indentify)) { // 编辑
            $ret = $serviceExam->update($examId, $profile);
        } else {
            $ret = $serviceExam->create($profile);
        }
        if ($ret == false) {
            throw new Zy_Core_Exception(405, "配置考试失败, 请重试");
        }
        return array();
    }
}