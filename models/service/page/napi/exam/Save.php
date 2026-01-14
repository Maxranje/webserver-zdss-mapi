<?php

class Service_Page_Napi_Exam_Save extends Service_Page_Napi_Exam_Service{

    public function execute () {
        if (!$this->checkMockStudent()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }
        $uid            = $this->adption["userid"];
        $examId         = empty($this->request['exam_id']) ? 0 : intval($this->request['exam_id']);
        $qid            = empty($this->request['qid']) ? 0 : intval($this->request['qid']);
        $studentAnswers = empty($this->request['student_answers']) ? array() : $this->request['student_answers'];
        $spendTime      = empty($this->request['spend_time']) ? 0 : intval($this->request['spend_time']);
        $expireTime     = empty($this->request['expire_time']) ? 0 : intval($this->request['expire_time']);

        $this->studentExamCheck($examId, $uid);

        // 校验参数
        if ($qid <= 0) {
            throw new Zy_Core_Exception(405, "未作答或回答信息不完整, 请重新提交或刷新重试!");
        }
        if (empty($studentAnswers[0]) || !is_array($studentAnswers[0])) {
            throw new Zy_Core_Exception(405, "未作答或回答信息不完整, 请重新提交~");
        }
        // 对比时间
        $spendTime1 = intval($this->examInfo["expire_time"]) * 60 - $expireTime;
        if ($spendTime <= 0 || $spendTime1 <= 0 || 
            $spendTime1 < $spendTime + intval($this->studentExam["spend_time"])) {
            throw new Zy_Core_Exception(405, "请认真思考作答后提交");
        }
        // first check user param
        foreach ($studentAnswers as $item) {
            if ($qid != $item["qid"]) {
                throw new Zy_Core_Exception(405, "试题不匹配, 请重新提交或刷新重试");
            }
            if (empty($item['answerContent'])) {
                throw new Zy_Core_Exception(405, "未作答或回答信息不完整, 请重新提交或刷新重试.");
            }
        } 

        $serviceQuestion = new Service_Data_Question();
        $currentQuestion = $serviceQuestion->getQuestionById($qid);
        if (empty($currentQuestion)) {
            Zy_Helper_Log::warning(sprintf("uid:%d, examid:%d, exam_save question empty, qid:%d", $uid, $examId, $qid));
            throw new Zy_Core_Exception(405, "试题不匹配, 请重新提交或刷新重试");
        }

        // 1-4 question find answer
        $saveAnswer = array();
        if (in_array($currentQuestion["type"], Service_Data_Question::QUESTION_TYPE_SIMPLE_MAP)) {
            $paper = new Service_Data_Paper();
            $paperQuestion = $paper->getPaperSingleQuestion(intval($this->examInfo["pid"]), $qid);
            if (empty($paperQuestion)) {
                Zy_Helper_Log::warning(sprintf("uid:%d, examid:%d, exam_save paper question empty, qid:%d", $uid, $examId, $qid));
                throw new Zy_Core_Exception(405, "试题不匹配, 请重新提交或刷新重试");
            }

            $serviceAnswer = new Service_Data_Answer();
            $currentAnswer = $serviceAnswer->getAnswerByQid($qid);
            if (empty($currentAnswer)) {
                Zy_Helper_Log::warning(sprintf("uid:%d, examid:%d, exam_save answer empty, qid:%d", $uid, $examId, $qid));
                throw new Zy_Core_Exception(405, "试题答案异常, 请重新提交或刷新重试");
            }
            $currentAnswer = array_column($currentAnswer, null, "id");

            // check user param
            $isCorrect = true;
            if ($currentQuestion["type"] == Service_Data_Question::QUESTION_TYPE_CHECKBOX) {
                $currentAnswerCorrectNum = 0;
                foreach ($currentAnswer as $v) {
                    $v["is_correct"] && $currentAnswerCorrectNum++;
                }
                if (count($studentAnswers) != $currentAnswerCorrectNum) {
                    $isCorrect = false;
                }
            }            
            foreach ($studentAnswers as $answer) {
                if (!isset($answer["answerId"]) || 
                    intval($answer["answerId"]) <= 0 || 
                    !isset($currentAnswer[$answer["answerId"]])) {
                    Zy_Helper_Log::warning(sprintf("uid:%d, examid:%d, exam_save answer id empty, info:%s", $uid, $examId, json_encode($studentAnswers)));
                    throw new Zy_Core_Exception(405, "未作答或回答信息不完整, 请重新提交或刷新重试");
                }
                // 多选, 如果回答不足, 也不给分
                if ($currentQuestion["type"] == Service_Data_Question::QUESTION_TYPE_FILL) {

                }
                if ($isCorrect) {
                    if ($currentQuestion["type"] == Service_Data_Question::QUESTION_TYPE_FILL) {
                        $isCorrect = $currentAnswer[$answer["answerId"]]["content"] == $answer["answerContent"];
                    } else {
                        $isCorrect = $currentAnswer[$answer["answerId"]]["is_correct"] == 1 ;
                    }
                }

                $saveAnswer[] = array(
                    "exam_id" => $examId,
                    "pid" => intval($this->examInfo["pid"]),
                    "student_uid" => $uid,
                    "qid" => $qid,
                    "type" => $currentQuestion["type"],
                    "answer_id" => intval($answer["answerId"]),
                    "answer_content" => $answer["answerContent"],
                    "score" => 0,
                    "spend_time" => $spendTime,
                    "is_correct" => 0,
                    "update_time" => time(),
                );
            }

            // 如果正确, 回填
            if ($isCorrect) {
                foreach ($saveAnswer as &$item) {
                    $item["score"] = $paperQuestion["score"];
                    $item["is_correct"] = 1;
                }
            }
        } else {
            $studentAnswers = $studentAnswers[0];
            // 主观题不关心内容了
            $saveAnswer[] = array(
                "exam_id" => $examId,
                "pid" => intval($this->examInfo["pid"]),
                "student_uid" => $uid,
                "qid" => $qid,
                "type" => $currentQuestion["type"],
                "answer_id" => 0,
                "answer_content" => $studentAnswers["answerContent"],
                "score" => 0,
                "spend_time" => $spendTime,
                "is_correct" => 0,
                "update_time" => time(),
            );            
        }

        $ret = $this->serviceExam->saveAnswer($examId, $qid, $uid, $saveAnswer, $spendTime1);
        if ($ret == false) {
            throw new Zy_Core_Exception(405, "提交失败, 重新提交或刷新重试");
        }

        // 评估拉取下一个级别
        if ($this->examInfo["paper_type"] == Service_Data_Paper::PAPER_TYPE_ASSESS) {
            $nextLevel = $this->serviceExam->getNextLevel ($examId, $uid, $this->studentExam);
            if ($nextLevel < $currentQuestion["level"]) {
                $level = -1;
            } else {
                $level = !in_array($nextLevel, Service_Data_Question::QUESTION_LEVEL_MAP) ? 
                    $currentQuestion["level"]:
                    $nextLevel;
            }
            return array("level" => $level); 
        }
        return array();
    }
}