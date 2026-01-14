<?php

class Service_Page_Mock_Review_Getai extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $studentUid     = empty($this->request['student_uid']) ? 0  : intval($this->request['student_uid']);
        $examId         = empty($this->request['exam_id']) ? 0 : intval($this->request['exam_id']);
        $qid            = empty($this->request['qid']) ? 0 : intval($this->request['qid']);        

        if ($studentUid <= 0 || $examId <= 0 || $qid <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 参数错误");
        }

        // ai参数
        $aiPrompt = Zy_Helper_Config::getAppConfig('aiprompt');
        if (empty($aiPrompt["appkey"]["deepseek"]) || empty($aiPrompt["prompt"]["exam_review"])) {
            throw new Zy_Core_Exception(405, "操作失败, 系统异常");
        }        

        // 获取考生考试信息
        $serviceExam = new Service_Data_Exam();
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
        if ($studentAnswer["type"] == Service_Data_Question::QUESTION_TYPE_SPEAK) {
            throw new Zy_Core_Exception(405, "暂不支持语音AI批改, 功能升级中敬请期待");
        }        

        // 获取试题信息
        $servicePaper = new Service_Data_Paper();
        $paperQuestion = $servicePaper->getPaperSingleQuestion(intval($studentAnswer["pid"]), $qid);
        if (empty($paperQuestion)) {
            throw new Zy_Core_Exception(405, "操作失败, 试题不存在");
        }

        $serviceQuestion = new Service_Data_Question();
        $currentQuestion = $serviceQuestion->getQuestionById($qid);
        if (empty($paperQuestion)) {
            throw new Zy_Core_Exception(405, "操作失败, 试题信息不存在");
        }
        $currentQuestion["score"] = $paperQuestion["score"];
        if ($currentQuestion["pre_meta_id"] > 0) {
            $serviceMeta = new Service_Data_Meta();
            $meta = $serviceMeta->getMetaById(intval($currentQuestion["pre_meta_id"]));
            $currentQuestion["meta"] = empty($meta["content"]) ? "" : $meta["content"];
            $currentQuestion["meta_type"] = empty($meta["meta_type"]) ? "" : $meta["meta_type"];
        }

        // 请求ai
        $res = array();
        try{
            $reqParams = array(
                "appKey" => $aiPrompt["appkey"]["deepseek"],
                "prompt" => $aiPrompt["prompt"]["exam_review"],
                "content" => array(),
            );
            if (!empty($currentQuestion["meta"])) {
                $reqParams["content"][] = sprintf("材料(%s): %s", 
                    $currentQuestion["meta_type"] == Service_Data_Meta::META_TYPE_AUDIO ? "音频" : "文本",
                    $currentQuestion["meta"]);
            }
            $reqParams["content"][] = sprintf("题目: %s",$currentQuestion["content"]);
            $reqParams["content"][] = sprintf("题目分: %s", $currentQuestion["score"]);
            $reqParams["content"][] = sprintf("考生作答: %s", $studentAnswer["answer_content"]);
            $reqParams["content"] = implode("\n", $reqParams["content"]);

            $ds = new Zy_Helper_Ai_Deepseek();
            $res = $ds->call($reqParams);
            if (empty($res) || empty($res["review_content"]) || $res["review_score"] > $currentQuestion["score"]) {
                throw new Zy_Core_Exception(405, "ai批改不是有效的json数据或返回数据格式异常" . json_encode($res));    
            }
            $res = array(
                "review_score" => $res["review_score"],
                "review_content" => $res["review_content"],
            );
        }catch (Exception $e) {
            Zy_Helper_Log::warning($e->getMessage());
            throw new Zy_Core_Exception(405, "操作失败, ai 批改获取失败, 请重试");
        }
        
        return $res;
    }
}