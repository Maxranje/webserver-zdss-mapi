<?php

class Service_Page_Mock_Review_Save extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $studentUid     = empty($this->request['student_uid']) ? 0  : intval($this->request['student_uid']);
        $examId         = empty($this->request['exam_id']) ? 0 : intval($this->request['exam_id']);
        $qid            = empty($this->request['qid']) ? 0 : intval($this->request['qid']);        
        $reviewContent  = empty($this->request['review_content_'.$qid]) ? "" : trim($this->request['review_content_'. $qid]);       
        $reviewScore    = empty($this->request['review_score_'.$qid]) ? 0 : intval($this->request['review_score_'.$qid]);        
        $reviewAI       = empty($this->request['review_ai_'.$qid]) ? "" : trim($this->request['review_ai_'.$qid]);   

        if ($studentUid <= 0 || $examId <= 0 || $qid <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 参数错误");
        }
        if (!empty($reviewContent) && !Zy_Helper_Utils::validateString($reviewContent, 1, 2000)) {
            throw new Zy_Core_Exception(405, "操作失败, 批改内容过多限定2000字符");
        }

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
            throw new Zy_Core_Exception(405, "操作失败, 考生必须已交卷或被强制踢出, 或批改中, 否则无法提交审批");
        }  

        // 获取考生考试信息
        $studentAnswer = $serviceExam->getStudentAnswerRecordByConds(array(
            "student_uid" => $studentUid,
            "qid" => $qid,
            "exam_id" => $examId,
        ));
        if (empty($studentAnswer)) {
            throw new Zy_Core_Exception(405, "操作失败, 考生未作答");
        }
        if (in_array($studentAnswer["type"], Service_Data_Question::QUESTION_TYPE_SIMPLE_MAP)) {
            throw new Zy_Core_Exception(405, "操作失败, 非主观题");
        }

        // 获取试题信息
        $servicePaper = new Service_Data_Paper();
        $paperQuestion = $servicePaper->getPaperSingleQuestion(intval($studentAnswer["pid"]), $qid);
        if (empty($paperQuestion)) {
            throw new Zy_Core_Exception(405, "操作失败, 试题不存在");
        }
        $reviewScore = $reviewScore <= 0 ? 0 : $reviewScore;
        if ($reviewScore > $paperQuestion["score"]) {
            throw new Zy_Core_Exception(405, "操作失败, 当前考题最高限定" . $paperQuestion["score"] . "分");
        }

        // 更新内容
        $profile = array(
            "review_content" => $reviewContent,
            "review_score" => $reviewScore,
            "review_ai" => $reviewAI ? 1 : 0,

            "studentExam" => $studentExam,
        );

        $ret = $serviceExam->reviewSave($examId, $studentUid, $qid, $profile);
        if ($ret == false) {
            throw new Zy_Core_Exception(405, "操作失败, 提交批改失败");
        }
        
        return array();
    }
}