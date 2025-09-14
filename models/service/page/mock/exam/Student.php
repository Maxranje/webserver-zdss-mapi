<?php

class Service_Page_Mock_Exam_Student extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $examId     = empty($this->request['exam_id']) ? 0 : intval($this->request['exam_id']);
        $studentUid = empty($this->request['student_uid']) ? 0 : intval($this->request['student_uid']);
        if ($examId <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 未选择要修改考试");
        }

        // 获取模考信息
        $serviceExam = new Service_Data_Exam();
        $exam = $serviceExam->getExamById($examId);
        if (empty($exam)) {
            throw new Zy_Core_Exception(405, "操作失败, 获取考试信息失败, 可能考试信息不存在或已被删除, 请重试");
        }

        $examAnswer = $serviceExam->getAnswerByExamId($examId, true, $studentUid);
        if (empty($examAnswer[$studentUid])) {
            return array();
        }
        $examAnswer = $examAnswer[$studentUid];   
        $examAnswer = array_column($examAnswer, null, "qid");

        // 如果是入学考试, 则随机出题, 要根据学员的做题情况判断
        $qids = array();
        if ($exam["paper_type"] == Service_Data_Paper::PAPER_TYPE_ASSESS) {
            $qids = Zy_Helper_Utils::arrayInt($examAnswer, "qid");
        } else {
            $servicePaper = new Service_Data_Paper();
            $qids = $servicePaper->getPaperQuestionIds(intval($exam["pid"]));
        }

        // 拉考题
        $serviceQuestion = new Service_Data_Question();
        $questions = $serviceQuestion->getQuestionByIds($qids, true);
        if (empty($questions)) {
            throw new Zy_Core_Exception(405, "操作失败, 获取考题信息失败, 请重试");
        }
        
        $ret = array();
        foreach ($questions as $item) {
            $qid = intval($item["qid"]);
            $level = empty($item["level"]) ? "" :Service_Data_Question::QUESTION_LEVEL_MAP_INFO[$item["level"]];
            $type  = empty($item["type"]) ? "" :Service_Data_Question::QUESTION_TYPE_MAP_INFO[$item["type"]];            
            $tmp = array(
                "answer_list_qid" => $qid,
                "answer_list_description" => $item["description"],
                "answer_list_type" => $type,
                "answer_list_coll" => empty($item["parent_id"]) ? "" : $item["parent_id"],
                "answer_list_level" => $level,
                "answer_list_answer_id" => empty($examAnswer[$qid]["answer_id"]) ? "" : $examAnswer[$qid]["answer_id"],
                "answer_list_answer_content" => empty($examAnswer[$qid]["answer_content"]) ? "" : $examAnswer[$qid]["answer_content"],
                "answer_list_is_correct" => empty($examAnswer[$qid]["is_correct"]) ? false : true,
                "answer_list_spend_time" => empty($examAnswer[$qid]["spend_time"]) ? "" : Zy_Helper_Utils::formatDurationForTime($examAnswer[$qid]["spend_time"]),
            );  
            $ret[] = $tmp;
        }
        return array(
            "rows" => array_values($ret),
        );
    }
}