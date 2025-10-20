<?php

class Service_Page_Napi_Exam_Service extends Zy_Core_Service{

    public $examInfo;
    public $studentExam; 

    public $serviceExam;

    // 基础检测
    public function studentExamCheck($examId, $uid) {
        // 查看考试状态
        $this->serviceExam = new Service_Data_Exam();
        $examInfo = $this->serviceExam->getExamById($examId);
        if (empty($examInfo) || !in_array($examInfo['status'], array(
            Service_Data_Exam::EXAM_STATUS_PENDING,
            Service_Data_Exam::EXAM_STATUS_ONGOING,
        ))) {
            throw new Zy_Core_Exception(405, "考试已结束");
        }
        if (time() < $examInfo['start_time']) {
            throw new Zy_Core_Exception(405, "考试未开始");
        }
        if (time() > $examInfo['end_time']) {
            throw new Zy_Core_Exception(405, "考试已结束");
        }

        $studentExam = $this->serviceExam->getExamStudent($examId, $uid);
        if (empty($studentExam)) {
            throw new Zy_Core_Exception(405, "您没有考试资格, 请联系老师");
        }
        if (!in_array($studentExam['status'], array(
            Service_Data_Exam::EXAM_STATUS_PENDING,
            Service_Data_Exam::EXAM_STATUS_ONGOING,
        ))) {
            throw new Zy_Core_Exception(405, "您的考试已结束");
        }

        $this->studentExam = $studentExam;
        $this->examInfo = $examInfo;
    }

    // 基础检测
    public function studentExamEndCheck($examId, $uid) {
        // 查看考试状态
        $this->serviceExam = new Service_Data_Exam();
        $examInfo = $this->serviceExam->getExamById($examId);
        if (empty($examInfo)) {
            throw new Zy_Core_Exception(405, "您没有考试");
        }

        $studentExam = $this->serviceExam->getExamStudent($examId, $uid);
        if (empty($studentExam)) {
            throw new Zy_Core_Exception(405, "您没有考试资格, 请联系老师");
        }
        if (in_array($studentExam['status'], array(
            Service_Data_Exam::EXAM_STATUS_PENDING,
            Service_Data_Exam::EXAM_STATUS_ONGOING
        ))) {
            throw new Zy_Core_Exception(405, "您的考试未结束");
        }

        $this->studentExam = $studentExam;
        $this->examInfo = $examInfo;
    } 
}