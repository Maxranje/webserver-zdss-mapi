<?php

class Service_Page_Mock_Paper_Detail extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限操作");
        }

        $pid = empty($this->request['pid']) ? 0 : intval($this->request['pid']);
        if ($pid <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 请求参数不正确");
        }

        $servicePaper = new Service_Data_Paper();
        $paper = $servicePaper->getPaperById($pid);
        if (empty($paper)) {
            throw new Zy_Core_Exception(405, "操作失败, 试卷不存在或已被删除");
        }

        $questions = array();
        $paperQuestions = $servicePaper->getPaperQuestions($pid);
        if (!empty($paperQuestions)) {
            $qids = Zy_Helper_Utils::arrayInt($paperQuestions, "qid");
            $serviceQuestion = new Service_Data_Question();
            $questions = $serviceQuestion->getQuestionByIds($qids, true);            
        }

        $paperInfo = array(
            "pid"             => $paper["pid"],
            "title"           => $paper["title"],
            "type"            => $paper["type"],
            "type_info"       => $paper["type"] == Service_Data_Paper::PAPER_TYPE_ASSESS ? "评估" : "常规",
            "frequency"       => $paper["frequency"],
            "total_question"  => $paper["total_question"],
            "remark"          => $paper["remark"],            
        );
        $paperInfo["input_total_question"] = $paper["type"] == Service_Data_Paper::PAPER_TYPE_ASSESS ? 
            Service_Data_Paper::INPUT_ASSESS_TOTAL_QUESTION : 
            Service_Data_Paper::INPUT_NORMAL_TOTAL_QUESTION;

        list($questions, $total) = $this->formatQuestion($questions, $paperQuestions);
        return array(
            "paper"     => $paperInfo,
            "questions" => $questions,
            "questions_total" => $total,
        );
    }

    // 格式化
    private function formatQuestion ($questions, $paperQuestions) { 
        if (empty($questions)) {
            return array(array(), 0);
        }
        $questions = array_column($questions, null, "qid");

        $options = array();
        $total = 0;
        $splitCnt = 30;
        foreach ($paperQuestions as $i => $v) {
            $total++;
            $qid = intval($v['qid']);
            $item = $questions[$qid];

            $level = empty($item["level"]) ? "" :Service_Data_Question::QUESTION_LEVEL_MAP_INFO[$item["level"]];
            $type  = empty($item["type"]) ? "" :Service_Data_Question::QUESTION_TYPE_MAP_INFO[$item["type"]];
            $score = empty($v["score"]) ? $item['score'] : $v["score"];

            $description = strlen($item["description"]) > $splitCnt ? substr($item["description"], 0, $splitCnt) . "..." : $item["description"];
            $content = empty($item["content"]) ? "" : strip_tags($item["content"]);
            $content = strlen($content) > $splitCnt ? substr($content, 0, $splitCnt) . "..." : $content;

            $isOff = $item["state"] != Service_Data_Question::QUESTION_ABLE;
            if ($item['parent_id'] > 0) {
                if (!isset($options[$item["parent_id"]])) {
                    $options[$item["parent_id"]] = array(
                        'title'         => sprintf("[题目组] %s", $description),
                        "children"      => array(),
                    );
                }
                $options[$item["parent_id"]]["children"][] = array(
                    'title' => sprintf("%d. %s", $i+1, $content),
                    "level" => $isOff ? "已下线" : sprintf("%s(%s)", $type, $level),
                    'qid'   => $item['qid'],
                    "score_info"=> $score . "分",
                    "state"     => $item['state'],
                    "score"     => $score,
                );
            } else {
                $options[] = array(
                    'title'     => sprintf("%d. [单项]%s", $i+1, $description),
                    'qid'       => $item['qid'], 
                    "level"     => $isOff ? "已下线" : sprintf("%s(%s)", $type, $level),
                    "score_info"=> $score . "分",
                    "state"     => $item['state'],
                    "score"     => $score,
                );
            }
        }
        return array(array_values($options), $total);  
    }    
}