<?php

class Service_Page_Mock_Review_Finish extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $studentUid     = empty($this->request['student_uid']) ? 0  : intval($this->request['student_uid']);
        $examId         = empty($this->request['exam_id']) ? 0 : intval($this->request['exam_id']);  

        if ($studentUid <= 0 || $examId <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 参数错误");
        }

        // 获取考生考试信息
        $serviceExam = new Service_Data_Exam();
        $studentExam = $serviceExam->getStudentRecordByConds(array(
            "student_uid" => $studentUid,
            "exam_id" => $examId,
        ));
        if (empty($studentExam)) {
            throw new Zy_Core_Exception(405, "操作失败, 考生未参加考试");
        }
        if (!in_array($studentExam["status"], [
            Service_Data_Exam::EXAM_STUDENT_STATUS_COMPLETE,
            Service_Data_Exam::EXAM_STUDENT_STATUS_REVIEWING,
            Service_Data_Exam::EXAM_STUDENT_STATUS_TERMINATED
        ])) {
            throw new Zy_Core_Exception(405, "操作失败, 考生必须已交卷或被强制踢出, 或批改中, 否则无法完结");
        }  

        // 获取所有试题和答案
        try {
            $reviewRet = $serviceExam->getStudentAnswerDetailForReview($examId, intval($studentExam['pid']), $studentUid);
        } catch (Exception $e) {
            throw new Zy_Core_Exception(405, $e->getMessage() . ", 请重试");
        }        
        
        $ret = $serviceExam->reviewFinish($examId, $studentUid, $reviewRet["studentScore"]);
        if ($ret == false) {
            throw new Zy_Core_Exception(405, "操作失败, 完结失败请重试");
        }
        return array();
    }
}