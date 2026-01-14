<?php

class Service_Page_Mock_Exam_Monitor extends Zy_Core_Service{

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
        $examId = intval($examInfo["id"]);
        $pid = intval($examInfo["pid"]);
        
        // 获取模考考生信息
        $students = $serviceExam->getStudentsByExamId(array($examId));
        $students = empty($students[$examId]) ? array() : $students[$examId];
        if (empty($students)) {
            throw new Zy_Core_Exception(405, "操作失败, 获取考试考生信息失败, 请重试");
        } 
        $serviceUser = new Service_Data_Profile();
        $uids = Zy_Helper_Utils::arrayInt($students, "student_uid");
        $userInfos = $serviceUser->getUserInfoByUids($uids);
        $userInfos = array_column($userInfos, null, "uid");
        
        // 试卷信息
        $servicePaper = new Service_Data_Paper();        
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
                "score" => $student["score"],
                "answer_cnt" => $answerCnt,
                "status_info" => "",
                "status_color" => "",
                "progress_info" => "0/".$examInfo["total_question"]."(完成度 0%)",
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
                if ($examInfo["paper_type"] == Service_Data_Paper::PAPER_TYPE_ASSESS && 
                    in_array($student["status"], [
                        Service_Data_Exam::EXAM_STUDENT_STATUS_REVIEWING,
                        Service_Data_Exam::EXAM_STUDENT_STATUS_FINISHED,
                        Service_Data_Exam::EXAM_STUDENT_STATUS_COMPLETE,
                ])) {
                    $tmp["progress"] = 100;
                    $tmp["progress_info"] = $answerCnt ."/". $answerCnt . " (完成: " . $tmp["progress"] . "%)"; 
                } else {
                    $answerCnt = $answerCnt > $examInfo["total_question"] ? $examInfo["total_question"] : $answerCnt;
                    $tmp["progress"] = sprintf("%.2f", $answerCnt / $examInfo["total_question"]) * 100;
                    $tmp["progress_info"] = $answerCnt ."/". $examInfo["total_question"] . " (完成: " . $tmp["progress"] . "%)"; 
                }
            } 


            $rows[] = $tmp;
        }

        $exam = $examInfo;
        if ($exam["paper_type"] == Service_Data_Paper::PAPER_TYPE_WORD) {
            $exam["paper_type_info"] = "单词本";
        } else if ($exam["paper_type"] == Service_Data_Paper::PAPER_TYPE_ASSESS) {
            $exam["paper_type_info"] = "评估";
        } else if ($exam["paper_type"] == Service_Data_Paper::PAPER_TYPE_NORMAL) {
            $exam["paper_type_info"] = "常规";
        } else {
            $exam["paper_type_info"] = "未知";
        }
        $exam["total_question"] = $examInfo["total_question"] . " 道";
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