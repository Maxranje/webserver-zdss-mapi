<?php

class Service_Page_Napi_Exam_Detail extends Service_Page_Napi_Exam_Service{

    public function execute () {
        if (!$this->checkMockStudent()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $uid = $this->adption["userid"];
        $examId = empty($this->request['exam_id']) ? 0 : intval($this->request['exam_id']);
        if ($uid <= 0 || $examId <= 0) {
            throw new Zy_Core_Exception(405, "无法获取模考信息");
        }
        
        $this->studentExamEndCheck($examId, $uid);
        $pid = intval($this->examInfo["pid"]);

        $serviceExam = new Service_Data_Exam();
        try {
            $ret = $serviceExam->getStudentAnswerDetailForMock($examId, $pid, $uid);
        } catch (Exception $e) {
            throw new Zy_Core_Exception(405, $e->getMessage() . ", 请重试");
        }

        $ret["apiExam"] = $this->examInfo;
        $ret["spendTime"] = $this->studentExam["spend_time"];
        return $ret;  
    }
}