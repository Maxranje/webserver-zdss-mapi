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
        // 获取模考学员信息
        $serviceExam = new Service_Data_Exam();
        $students = $serviceExam->getStudentByExamIds(array($this->examInfo["id"]));
        $students = empty($students[$this->examInfo["id"]]) ? array() : $students[$this->examInfo["id"]];
        if (empty($students)) {
            throw new Zy_Core_Exception(405, "操作失败, 获取考试学员信息失败, 请重试");
        } 

        $ret = array(
            "exam_id" => $this->examInfo["id"],
            "pid" => $this->examInfo["pid"],
            "teacher_uid" => $this->examInfo["teacher_uid"],
            "expire_time" => $this->examInfo["expire_time"],
            "remark" => $this->examInfo["remark"],
            "start_end" => sprintf("%d,%d", $this->examInfo["start_time"], $this->examInfo["end_time"]),
            "student_uids"  =>  Zy_Helper_Utils::arrayInt($students, "student_uid"),
        );
        return array("exam" => $ret);
    }

    // 监控
    public function formatMonitor() {
        $serviceExam = new Service_Data_Exam();
        $servicePaper = new Service_Data_Paper();
        $serviceQuestion = new Service_Data_Question();
        $pid    = intval($this->examInfo["pid"]);
        $examId = intval($this->examInfo["id"]);        
        
        // 获取模考学员信息
        $students = $serviceExam->getStudentByExamIds(array($examId), true);
        $students = empty($students[$examId]) ? array() : $students[$examId];
        if (empty($students)) {
            throw new Zy_Core_Exception(405, "操作失败, 获取考试学员信息失败, 请重试");
        } 
        
        $paper = $servicePaper->getPaperById($pid);
        $questionIds = $servicePaper->getPaperQuestionIds($pid);
        $studentAnswer = $serviceExam->getAnswerByExamId($examId, true);

        $rows = array();
        $charts = array(
            'xAxis' => array(),
            "serise" => array(
                array(
                    "name"=> "已完成",
                    "type"=> "bar",
                    "stack"=> "total",
                    "label"=> array(
                        "show"=> true
                    ),
                    "data"=> array(),
                ),                
                array(
                    "name"=> "待完成",
                    "type"=> "bar",
                    "stack"=> "total",
                    "label"=> array(
                        "show"=> true
                    ),
                    "data"=> array(),
                )                
            ),
        );

        foreach ($students as $student) {
            if (empty($student["student"]['uid'])) {
                continue;
            }
            $uid = $student["student"]["uid"];
            $studentInfo = $student["student"];

            $tmp = array(
                "student_uid" => $uid,
                "nickname" => $studentInfo["nickname"],
                "school" => $studentInfo["school"],
                "graduate" => $studentInfo["graduate"],
                "update_time" => date("Y-m-d H:i:s", $student["update_time"]),   
                "progress" => 0,
                "status" => $student["status"],
                "progress_info" => "0/0 (完成度 0%)"
            );

            if (!empty($studentAnswer[$uid])) {
                $answer = $studentAnswer[$uid];
                $answer = array_column($answer, null, "qid");

                $tmp["progress"] = intval(floatval(sprintf("%.2f", count($answer) / count($questionIds))) * 100);
                $tmp["progress_info"] = count($answer) ."/". count($questionIds) . " (完成: " . $tmp["progress"] . "%)"; 

                $charts["xAxis"][] = $studentInfo["nickname"];
                $charts["serise"][1]["data"][] = count($questionIds) - count($answer);
                $charts["serise"][0]["data"][] = count($answer);
            }
            $rows[] = $tmp;
        }

        $exam = $this->examInfo;
        if ($exam["paper_type"] == Service_Data_Paper::PAPER_TYPE_WORD) {
            $exam["paper_type_info"] = "单词本";
        } else if ($exam["paper_type"] == Service_Data_Paper::PAPER_TYPE_ASSESS) {
            $exam["paper_type_info"] = "入学评估";
        } else{
            $exam["paper_type_info"] = "常规";
        }
        $exam["question_cnt"] = count($questionIds) . " 道";
        $exam["total_score"] = $exam["total_score"] . " 分";

        
        
        // 输出内容
        $ret = array(
            "exam" => $exam,
            "paper" => $paper,
            "charts" => $charts,
            "rows" => $rows,
            "rows_total" => count($rows),
        );
        return $ret;
    }
}