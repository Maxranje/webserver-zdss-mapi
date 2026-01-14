<?php

class Service_Page_Napi_Exam_Init extends Service_Page_Napi_Exam_Service{

    public function execute () {
        if (!$this->checkMockStudent()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }
        $uid = $this->adption["userid"];
        $examId = empty($this->request['exam_id']) ? 0 : intval($this->request['exam_id']);
        $sign = empty($this->request['sign']) ? "" : trim($this->request['sign']);


        $this->studentExamCheck($examId, $uid);
        
        // 启动开始
        if ($this->studentExam['status'] == Service_Data_Exam::EXAM_STUDENT_STATUS_PENDING || 
            $this->examInfo["status"] == Service_Data_Exam::EXAM_STATUS_PENDING) {
            $serviceExam = new Service_Data_Exam();
            $ret = $serviceExam->studentStart($examId, $uid, 
                $this->studentExam["status"] == Service_Data_Exam::EXAM_STUDENT_STATUS_PENDING, 
                $this->examInfo["status"] == Service_Data_Exam::EXAM_STATUS_PENDING);
            if ($ret == false) {
                throw new Zy_Core_Exception(405, "更新状态失败, 请重试");
            }
        }
        
        return array(
            "paperType" => $this->examInfo["paper_type"],
        );
    }
}