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

        $paper = $this->formatPaper($paper);
        $questions = $this->formatQuestion($questions, $paperQuestions);
        return array(
            "paper"     => $paper,
            "questions" => $questions,
            "questions_total" => count($questions),
        );
    }

    // 格式化
    private function formatPaper ($paper) { 
        $pid = intval($paper["pid"]);

        $sourceInfos = array();
        $serviceData = new Service_Data_Paper();
        $sourceIds = $serviceData->getSourceByPaperIds(array($pid));
        if (!empty($sourceIds[$pid])) {
            $sourceIds = Zy_Helper_Utils::arrayInt($sourceIds[$pid], "source_id");
            $serviceData = new Service_Data_Source();
            $sourceInfos = $serviceData->getSourceByIds($sourceIds);
        }

        $subjectInfo = array();
        if (!empty($paper["subject_id"])) {
            $serviceData = new Service_Data_Subject();
            $subjectInfo = $serviceData->getSubjectById(intval($paper["subject_id"]));
        }
        

        $ret = array();
        $ret["pid"]             = $paper["pid"];
        $ret["title"]           = $paper["title"];
        $ret["type"]            = $paper["type"];
        $ret["type_info"]       = $paper["type"] == 2 ? "入学测验" : ($paper["type"] == 3 ? "单词本" : "常规");
        $ret["weight_score"]    = $paper["weight_score"];
        $ret["frequency"]       = $paper["frequency"];
        $ret["remark"]          = $paper["remark"];
        $ret["subject_name"]    = empty($subjectInfo["name"]) ? "暂无" : $subjectInfo["name"];
        $ret["source_list"]     = array_column($sourceInfos, "name");
        return $ret;
    }   

    // 格式化
    private function formatQuestion ($questions, $paperQuestions) { 
        if (empty($questions)) {
            return array();
        }

        $paperQuestions = array_column($paperQuestions, null, "qid");

        $options = array();
        foreach ($questions as $i => $item) {
            $level = empty($item["level"]) ? "" :Service_Data_Question::QUESTION_LEVEL_MAP_INFO[$item["level"]];
            $type  = empty($item["type"]) ? "" :Service_Data_Question::QUESTION_TYPE_MAP_INFO[$item["type"]];
            $score = empty($paperQuestions[$item["qid"]]["score"]) ? $item['score'] : $paperQuestions[$item["qid"]]["score"];
            if ($item['parent_id'] > 0) {
                if (!isset($options[$item["parent_id"]])) {
                    $options[$item["parent_id"]] = array(
                        'title'         => sprintf("第%d题 - 题目组", $i+1),
                        "children"      => array(),
                    );
                }
                $options[$item["parent_id"]]["children"][] = array(
                    'title' => empty($item['description']) ? strip_tags($item["content"]) : $item["description"],
                    "level" => sprintf("%s(%s)", $type, $level),
                    'qid'   => $item['qid'],
                    "score_info"=> $score . "分",
                    "score"     => $score,
                );
            } else {
                $options[] = array(
                    'title'     => sprintf("第%d题 - 单项题", $i+1),
                    'qid'       => $item['qid'], 
                    "level"     => sprintf("%s(%s)", $type, $level),
                    "score_info"=> $score . "分",
                    "score"     => $score,
                );
            }
        }
        return array_values($options);  
    }    
}