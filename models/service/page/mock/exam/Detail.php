<?php

class Service_Page_Mock_Exam_Detail extends Zy_Core_Service{
    private $examInfo;

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $indentify = empty($this->request['exam_indentify']) ? "" : trim($this->request['exam_indentify']);
        if (empty($indentify)) {
            return array();
        }
        if (strpos($indentify, "EX") === FALSE) {
            throw new Zy_Core_Exception(405, "参数异常");
        }

        // 获取模考信息
        $serviceExam = new Service_Data_Exam();
        $examInfo = $serviceExam->getExamByIndentify($indentify);
        if (empty($examInfo)) {
            throw new Zy_Core_Exception(405, "操作失败, 获取考试信息失败, 可能考试信息不存在或已被删除, 请重试");
        }

        // get user
        $students = $serviceExam->getStudentsByExamId(array($examInfo["id"]));
        $students = empty($students[$examInfo["id"]]) ? array() : $students[$examInfo["id"]];
        if (empty($students)) {
            throw new Zy_Core_Exception(405, "操作失败, 获取考试考生信息失败, 请重试");
        }

        $ret = array(
            "exam_id" => $examInfo["id"],
            "pid" => $examInfo["pid"],
            "paper_type" => $examInfo["paper_type"],
            "pid_arr" => sprintf("%s_%s", $examInfo["pid"], $examInfo["paper_type"]),
            "teacher_uid" => $examInfo["teacher_uid"],
            "expire_time" => $examInfo["expire_time"],
            "total_question" => $examInfo["total_question"],
            "remark" => $examInfo["remark"],
            "start_end" => sprintf("%d,%d", $examInfo["start_time"], $examInfo["end_time"]),
            "student_uids"  =>  Zy_Helper_Utils::arrayInt($students, "student_uid"),
        );
        return $ret;
    }
}