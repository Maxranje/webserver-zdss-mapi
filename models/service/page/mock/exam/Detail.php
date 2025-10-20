<?php

class Service_Page_Mock_Exam_Detail extends Zy_Core_Service{
    private $examInfo;

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $examId     = empty($this->request['exam_id']) ? 0 : intval($this->request['exam_id']);
        $isEdit     = empty($this->request["is_edit"]) ? false : true;
        $isMonitor  = empty($this->request["is_monitor"]) ? false : true;
        if ($examId <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 未选择要修改考试");
        }

        // 获取模考信息
        $serviceExam = new Service_Data_Exam();
        $this->examInfo = $serviceExam->getExamById($examId);
        if (empty($this->examInfo)) {
            throw new Zy_Core_Exception(405, "操作失败, 获取考试信息失败, 可能考试信息不存在或已被删除, 请重试");
        }
        if ($isEdit && $this->examInfo["status"] != 1) {
            throw new Zy_Core_Exception(405, "操作失败, 本次考试已经开始或已经结束, 无法编辑修改, 请刷新");
        }
        
        if ($isEdit) {
            return $this->formatEdit();
        }
        if ($isMonitor) {
            return $this->formatMonitor();
        }

        throw new Zy_Core_Exception(405, "操作失败, 无效查询");        
    }

    // 编辑
    public function formatEdit() {
        // 获取模考考生信息
        $serviceExam = new Service_Data_Exam();
        $students = $serviceExam->getStudentsByExamId(array($this->examInfo["id"]));
        $students = empty($students[$this->examInfo["id"]]) ? array() : $students[$this->examInfo["id"]];
        if (empty($students)) {
            throw new Zy_Core_Exception(405, "操作失败, 获取考试考生信息失败, 请重试");
        }

        $ret = array(
            "exam_id" => $this->examInfo["id"],
            "pid" => $this->examInfo["pid"],
            "paper_type" => $this->examInfo["paper_type"],
            "pid_info" => sprintf("%s_%s", $this->examInfo["pid"], $this->examInfo["paper_type"]),
            "teacher_uid" => $this->examInfo["teacher_uid"],
            "expire_time" => $this->examInfo["expire_time"],
            "total_question" => $this->examInfo["total_question"],
            "remark" => $this->examInfo["remark"],
            "start_end" => sprintf("%d,%d", $this->examInfo["start_time"], $this->examInfo["end_time"]),
            "student_uids"  =>  Zy_Helper_Utils::arrayInt($students, "student_uid"),
        );
        return array("exam" => $ret);
    }

    // 监控
    public function formatMonitor() {
        $serviceUser = new Service_Data_Profile();
        $serviceExam = new Service_Data_Exam();
        $servicePaper = new Service_Data_Paper();

        $pid    = intval($this->examInfo["pid"]);
        $examId = intval($this->examInfo["id"]);        
        
        // 获取模考考生信息
        $students = $serviceExam->getStudentsByExamId(array($examId));
        $students = empty($students[$examId]) ? array() : $students[$examId];
        if (empty($students)) {
            throw new Zy_Core_Exception(405, "操作失败, 获取考试考生信息失败, 请重试");
        } 
        $uids = Zy_Helper_Utils::arrayInt($students, "student_uid");
        $userInfos = $serviceUser->getUserInfoByUids($uids);
        $userInfos = array_column($userInfos, null, "uid");
        
        // 试卷信息
        $paper = $servicePaper->getPaperById($pid);

        // 考生作答数
        $studentAnswerCnt = $serviceExam->getAnswerCntByExamId($examId);

        $rows = array();
        foreach ($students as $student) {
            if (empty($userInfos[$student["student_uid"]]["nickname"])) {
                continue;
            }
            $uid = $student["student_uid"];
            $studentInfo = $userInfos[$student["student_uid"]];
            $answerCnt = empty($studentAnswerCnt[$uid]) || $studentAnswerCnt[$uid]<=0 ? 0 :$studentAnswerCnt[$uid]; 

            $tmp = array(
                "student_uid" => $uid,
                "nickname" => $studentInfo["nickname"],
                "school" => $studentInfo["school"],
                "graduate" => $studentInfo["graduate"],
                "update_time" => date("Y-m-d H:i:s", $student["update_time"]),   
                "progress" => 0,
                "status" => $student["status"],
                "answer_cnt" => $answerCnt,
                "status_info" => "",
                "status_color" => "",
                "progress_info" => "0/".$this->examInfo["total_question"]."(完成度 0%)",
            );

            if ($student["status"] == Service_Data_Exam::EXAM_STUDENT_STATUS_PENDING) {
                $tmp["status_info"] = "待考试";
                $tmp["status_color"] = "#9ca3af";
            } else if ($student["status"] == Service_Data_Exam::EXAM_STUDENT_STATUS_ONGOING) {
                $tmp["status_info"] = "模考中";
                $tmp["status_color"] = "#60a5fa";
            } else if ($student["status"] == Service_Data_Exam::EXAM_STUDENT_STATUS_TERMINATED) {
                $tmp["status_info"] = "被踢出";
                $tmp["status_color"] = "#374151";
            } else if ($student["status"] == Service_Data_Exam::EXAM_STUDENT_STATUS_COMPLETE) {
                $tmp["status_info"] = "交卷";
                $tmp["status_color"] = "#10b981";
            } else if ($student["status"] == Service_Data_Exam::EXAM_STUDENT_STATUS_REVIEWING) {
                $tmp["status_info"] = "批改中";
                $tmp["status_color"] = "#10b981";
            } else if ($student["status"] == Service_Data_Exam::EXAM_STUDENT_STATUS_FINISHED) {
                $tmp["status_info"] = "结束";
                $tmp["status_color"] = "#343a40";
            }

            if ($answerCnt > 0) {
                if ($this->examInfo["paper_type"] == Service_Data_Paper::PAPER_TYPE_ASSESS && 
                    in_array($student["status"], [
                        Service_Data_Exam::EXAM_STUDENT_STATUS_REVIEWING,
                        Service_Data_Exam::EXAM_STUDENT_STATUS_FINISHED,
                        Service_Data_Exam::EXAM_STUDENT_STATUS_COMPLETE,
                ])) {
                    $tmp["progress"] = 100;
                    $tmp["progress_info"] = $answerCnt ."/". $answerCnt . " (完成: " . $tmp["progress"] . "%)"; 
                } else {
                    $answerCnt = $answerCnt > $this->examInfo["total_question"] ? $this->examInfo["total_question"] : $answerCnt;
                    $tmp["progress"] = sprintf("%.2f", $answerCnt / $this->examInfo["total_question"]) * 100;
                    $tmp["progress_info"] = $answerCnt ."/". $this->examInfo["total_question"] . " (完成: " . $tmp["progress"] . "%)"; 
                }
            } 


            $rows[] = $tmp;
        }

        $exam = $this->examInfo;
        if ($exam["paper_type"] == Service_Data_Paper::PAPER_TYPE_WORD) {
            $exam["paper_type_info"] = "单词本";
        } else if ($exam["paper_type"] == Service_Data_Paper::PAPER_TYPE_ASSESS) {
            $exam["paper_type_info"] = "评估";
        } else{
            $exam["paper_type_info"] = "常规";
        }
        $exam["total_question"] = $this->examInfo["total_question"] . " 道";
        $exam["total_score"] = $exam["total_score"] . " 分";

        
        
        // 输出内容
        $ret = array(
            "exam" => $exam,
            "paper" => $paper,
            "rows" => $rows,
            "rows_total" => count($rows),
        );
        return $ret;
    }
}