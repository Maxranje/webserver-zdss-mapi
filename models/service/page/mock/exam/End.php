<?php

class Service_Page_Mock_Exam_End extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $examId         = empty($this->request['exam_id']) ? 0 : intval($this->request['exam_id']);
        $studentUid     = empty($this->request['student_uid']) ? 0 : intval($this->request["student_uid"]);

        if ($examId <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 未选定考试");
        }

        // 获取模考信息
        $serviceExam = new Service_Data_Exam();
        $exam = $serviceExam->getExamById($examId);
        if (empty($exam)) {
            throw new Zy_Core_Exception(405, "操作失败, 获取考试信息失败, 请重试");
        }
        if ($exam["status"] == Service_Data_Exam::EXAM_STATUS_COMPLETE || 
            $exam["status"] == Service_Data_Exam::EXAM_STATUS_TERMINATED) {
            throw new Zy_Core_Exception(405, "操作失败, 考试已完成或已强制结束, 请重试");
        }
        if (!$this->isModeAble(Service_Data_Roles::ROLE_MODE_MOCK_DONE) && $exam["teacher_uid"] != OPERATOR) {
            throw new Zy_Core_Exception(405, "非特定权限或监考老师, 无权限操作");
        }
                
        $students = $serviceExam->getStudentsByExamId(array($examId));
        $students = empty($students[$examId]) ? array() : $students[$examId];
        if (empty($students)) {
            throw new Zy_Core_Exception(405, "操作失败, 无法获取考生信息, 请重试");
        }

        if ($studentUid > 0) {
            $ret = $this->studentEnd($studentUid, $exam, $students);
        } else {
            $ret = $this->examEnd($exam, $students);
        }
        if (false == $ret) {
            throw new Zy_Core_Exception(405, "操作失败, 系统异常, 请重试");
        }
        return array();
    }

    // 单人结束
    private function studentEnd ($studentUid, $exam, $students) {
        // 循环, 判断是否所有人都已经结束
        $students = array_column($students, null, "student_uid");
        if (empty($students[$studentUid])) {
            throw new Zy_Core_Exception(405, "操作失败, 考试中无关联当前考生, 请重试");
        }
        if ($students[$studentUid]["status"] != Service_Data_Exam::EXAM_STUDENT_STATUS_PENDING && 
            $students[$studentUid]["status"] != Service_Data_Exam::EXAM_STUDENT_STATUS_ONGOING) {
            throw new Zy_Core_Exception(405, "操作失败, 考生考试已结束, 无法强制结束");
        }
        $serviceExam = new Service_Data_Exam();
        return $serviceExam->studentTerminatedEnd(intval($exam["id"]), $studentUid);
    }

    // 全体结束
    private function examEnd ($exam, $students) {
        // 循环, 判断是否所有人都已经结束
        $studentUids = array();
        foreach ($students as $v) {
            if ($v["status"] != Service_Data_Exam::EXAM_STUDENT_STATUS_PENDING && 
                $v["status"] != Service_Data_Exam::EXAM_STUDENT_STATUS_ONGOING) {
                continue;
            }
            $studentUids[] = $v["student_uid"];
        }

        $serviceExam = new Service_Data_Exam();
        return $serviceExam->examEnd($exam["id"], $studentUids);
    }    
}