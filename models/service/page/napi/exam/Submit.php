<?php

class Service_Page_Napi_Exam_Submit extends Service_Page_Napi_Exam_Service{

    public function execute () {
        if (!$this->checkMockStudent()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $uid        = $this->adption["userid"];
        $examId     = empty($this->request['exam_id']) ? 0 : intval($this->request['exam_id']);
        $expireTime = empty($this->request['expire_time']) ? 0 : intval($this->request['expire_time']);
        
        if ($examId <= 0) {
            throw new Zy_Core_Exception(405, "参数有误, 请刷新重试");
        }

        $this->studentExamCheck($examId, $uid);
        if ($this->examInfo["status"] != Service_Data_Exam::EXAM_STATUS_ONGOING || 
            $this->studentExam['status'] != Service_Data_Exam::EXAM_STUDENT_STATUS_ONGOING) {
            throw new Zy_Core_Exception(405, "考试状态异常, 请刷新重试");
        }
        $spendTime = intval($this->examInfo["expire_time"]) * 60 - $expireTime;
        if ($spendTime < $this->studentExam["spend_time"]) {
            throw new Zy_Core_Exception(405, "考试时间状态异常, 请刷新重试");
        }

        $ret = $this->serviceExam->studentSubmit($examId, $uid, $spendTime);
        if ($ret == false) {
            throw new Zy_Core_Exception(405, "交卷异常, 请重试");
        }
        return array();
    }
}