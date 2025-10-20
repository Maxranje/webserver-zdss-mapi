<?php

class Service_Page_Napi_Exam_Get extends Service_Page_Napi_Exam_Service{

    public function execute () {
        if (!$this->checkMockStudent()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }
        $uid = $this->adption["userid"];
        $examId = empty($this->request['exam_id']) ? 0 : intval($this->request['exam_id']);
        
        $this->studentExamCheck($examId, $uid);
        $pid = intval($this->examInfo["pid"]);

        $serviceExam = new Service_Data_Exam();
        try {
            if ($this->examInfo["paper_type"] == Service_Data_Paper::PAPER_TYPE_NORMAL) {
                $ret = $serviceExam->getStudentAnswerDetailForNormalExam($examId, $pid, $uid);
            } else if ($this->examInfo["paper_type"] == Service_Data_Paper::PAPER_TYPE_ASSESS) {
                $ret = $serviceExam->getStudentAnswerDetailForAssessExam($examId, $pid, $uid);
            }
        } catch (Exception $e) {
            throw new Zy_Core_Exception(405, $e->getMessage() . ", 请重试");
        }

        $ret["currentQid"]  = $ret["questions"][0]["qid"];
        $ret["expireTime"]   = ($this->examInfo["expire_time"] * 60) - $this->studentExam["spend_time"];
        return $ret;  
    }
}