<?php

class Service_Page_Mock_Question_Detail extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限操作");
        }

        $qid        = empty($this->request['qid']) ? 0 : intval($this->request['qid']);
        $preview    = empty($this->request['preview']) ? false : true;

        // response
        if ($qid <= 0) {
            throw new Zy_Core_Exception(405, "参数异常, 无法获取试题信息");
        }

        // get question info
        $serviceQuestion = new Service_Data_Question();
        $question = $serviceQuestion->getQuestionById($qid);
        if (empty($question)) {
            throw new Zy_Core_Exception(405, "操作失败, 无法找到试题或试题已被删除");
        }
        
        $questions = array();
        // 单项题
        if ($question["is_coll"] <= 0 && $question["parent_id"] <= 0) {
            $questions[] = $question;
        } else { 
            // 题目组
            $parentId = $question["is_coll"] == 1 ? $question["qid"] : $question["parent_id"];
            $questions = $serviceQuestion->getQuestionByParentId(intval($parentId));            
        }
        if (empty($questions)) {
            throw new Zy_Core_Exception(405, "操作失败, 无法找到试题或试题已被删除!");
        }

        $qids = Zy_Helper_Utils::arrayInt($questions, "qid");

        $serviceAnswer = new Service_Data_Answer();
        $answers = $serviceAnswer->getAnswerByQids($qids);

        // 预览
        if ($preview) {    
            return $this->formatPreview($qid, $questions, $answers);
        }
        
        //编辑
        return $this->formatBase($qid, $questions, $answers);
    }

    // preview
    private function formatPreview ($currentId, $questions, $answers) {
        $result = array(
            array(
                "type" => "alert",
                "level" => "warning",
                "showIcon" => true,
                "body" => "与学员看到最终样式有一定区别, 预览只作为参考使用"
            ),
        );
        // 物料
        if (!empty($questions[0]["pre_meta_id"])) {
            $serviceData = new Service_Data_Meta();
            $meta = $serviceData->getMetaById(intval($questions[0]["pre_meta_id"]));
            if (!empty($meta["content"])) {
                $result[] =  array(
                    "type"  => "fieldSet",
                    "title" => "前置材料",
                    "body"  => array(
                        "type"  => "html",
                        "html"   =>$meta['content']
                    )
                );
            }
        }

        // 试题
        $questionsTpl = array(
            "type"=>  "tabs",
            "tabsMode"=> "line",
            "className" => "mt-4",
            "defaultKey" => 0,           
            "tabs"=>  []
        );
        foreach ($questions as $index => $question) {
            if ($question["qid"] == $currentId) {
                $questionsTpl["defaultKey"] = $index;
            }  
            $tab = array(
                "title" => sprintf("Question %d", $question["qid"]),            
                "tab" => array(
                    array(
                        "type"=> "fieldSet",
                        "title"=> "Question",
                        "body"=> [
                            array(
                                "type"=> "html",
                                "html"=> $question["content"],
                            ),
                        ]                        
                    )
                )
            );
            if (!empty($answers[$question['qid']])) {
                $answer = $answers[$question['qid']];
                shuffle($answer);
                $answer = array_values($answer);
                $answerHtml = "";
                $answerCorrect = array();
                foreach ($answer as $k => $v) {
                    $key = Service_Data_Question::QUESTION_PREFIX[$k];
                    if ($v["type"] == Service_Data_Question::QUESTION_TYPE_FILL) {
                        $key = $k+1;
                    }
                    $answerHtml .= sprintf("<p>%s. %s</p>", $key, $v["content"]);
                    if ($v["is_correct"] && in_array($v["type"], [
                        Service_Data_Question::QUESTION_TYPE_CHECK,
                        Service_Data_Question::QUESTION_TYPE_CHECKBOX,
                        Service_Data_Question::QUESTION_TYPE_RAIDO,
                        Service_Data_Question::QUESTION_TYPE_FILL,
                    ])) {
                        $answerCorrect[] = $key;
                    }
                }
                $answerCorrect = implode(",", $answerCorrect);
                if ($v["type"] == Service_Data_Question::QUESTION_TYPE_FILL) {
                    $answerCorrect = $answerHtml;
                } else {
                    $tab["tab"][0]["body"][] = array(
                        "type"=> "html",
                        "html"=> $answerHtml
                    );
                }  
                // 正确答案
                if (!empty($answerCorrect)) {
                    $tab["tab"][] = array(
                        "type"  => "fieldSet",
                        "title" => "Answer",
                        "body"  => array(
                            "type"  => "html",
                            "html"   => $answerCorrect,
                        )                    
                    );
                }                    
            }
            //解析
            if (!empty($question["explan"])) {
                $tab["tab"][] = array(
                    "type"  => "fieldSet",
                    "title" => "Explanation",
                    "body"  => array(
                        "type"  => "html",
                        "html"   => $question["explan"],
                    )                    
                );
            }  
            $questionsTpl["tabs"][] = $tab;
        }

        $result[] = $questionsTpl;
        return $result;
    }

    // eidt
    private function formatBase ($currentId, $questions, $answers) {
        // 物料
        $meta = array();
        if (!empty($questions[0]["pre_meta_id"])) {
            $serviceData = new Service_Data_Meta();
            $meta = $serviceData->getMetaById(intval($questions[0]["pre_meta_id"]));
        }

        // 壳子id
        $parentId = intval($questions[0]["parent_id"]);          

        // 题目组, meta必须存在
        if ($parentId > 0 && empty($meta["content"])) { 
            throw new Zy_Core_Exception(405, "操作失败, 获取题目组中前置物料失败, 请刷新重试");
        }      
        
        // 标签
        $serviceData = new Service_Data_Questiontag();
        $qids = Zy_Helper_Utils::arrayInt($questions, "qid");
        $qtMap = $serviceData->getTagidsByQids($qids);

        // 答案
        if (!empty($answers)) {
            foreach ($answers as $qid => $v) {
                foreach ($v as $j =>  $vv) {
                    $vv = array(
                        "answer_id"         => $vv["id"],
                        "is_answer"         => !empty($vv["is_correct"]) ? 1 : 0,
                        "answer_content"    => $vv["content"],
                    );
                    $v[$j] = $vv;
                }
                $answers[$qid] = $v;
            }
        }

        // 结构数据
        $question = array();
        if ($parentId <= 0 ) {
            $profile = $questions[0];
            $answer = empty($answers[$profile["qid"]]) ? array() : $answers[$profile["qid"]];

            $question["qid"]                            = $profile["qid"];
            $question["type"]                           = $profile["type"];
            $question["title"]                          = "试题:" . $profile["qid"] . " - 编辑中";
            $question["defaultKey"]                     = 0; 
            $question['pre_meta_id']                    = $profile["pre_meta_id"];            
            $question["single_question_type"]           = $profile["type"];
            $question["single_question_level"]          = $profile["level"];
            $question["single_question_score"]          = $profile["score"];
            $question["single_question_tag_ids"]        = empty($qtMap[$profile["qid"]]) ? array() : Zy_Helper_Utils::arrayInt($qtMap[$profile["qid"]]);
            $question["single_question_subject_id"]     = $profile["subject_id"];
            $question["single_question_description"]    = $profile["description"];
            $question["single_question_content"]        = $profile["content"];
            $question["single_question_audio"]          = $profile["audio"];
            $question["single_answer_combo_radio"]      = $profile["type"] != Service_Data_Question::QUESTION_TYPE_RAIDO ? array() : $answer;
            $question["single_answer_combo_check"]      = $profile["type"] != Service_Data_Question::QUESTION_TYPE_CHECK ? array() : $answer;
            $question["single_answer_combo_checkbox"]   = $profile["type"] != Service_Data_Question::QUESTION_TYPE_CHECKBOX ? array() : $answer;
            $question["single_answer_combo_fill"]       = $profile["type"] != Service_Data_Question::QUESTION_TYPE_FILL ? array() : $answer;
            $question["single_question_pre_meta_combo"] = array();
            $question["single_answer_explan_combo"]     = array();

            if (!empty($profile["explan"])) {
                $question["single_answer_explan_combo"][] = array(
                    "single_answer_explan" => $profile["explan"],
                );
            }            
            if (!empty($meta["content"])) {
                $question["single_question_pre_meta_combo"][] = array(
                    "single_question_pre_meta" => $meta["content"],
                );
            }
            
        } else {
            $question["qid"]                            = $currentId;
            $question["title"]                          = "题目组:" . $parentId . " - 编辑中";
            $question["defaultKey"]                     = 1;                
            $question["batch_question_combo_item"]      = array();
            $question["batch_question_pre_meta"]        = $meta["content"];
            $question["batch_question_parent_qid"]      = $parentId;
            $question["pre_meta_id"]                    = $meta["id"];    
            $question["batch_question_description"]     = $questions[0]["description"];
         
            foreach ($questions as $profile) {
                $answer = empty($answers[$profile["qid"]]) ? array() : $answers[$profile["qid"]];
                $batchTmp = array(
                    "batch_question_qid"            => $profile["qid"],
                    "batch_question_type"           => $profile["type"],
                    "batch_question_level"          => $profile["level"],
                    "batch_question_score"          => $profile["score"],
                    "batch_question_tag_ids"        => empty($qtMap[$profile["qid"]]) ? array() : Zy_Helper_Utils::arrayInt($qtMap[$profile["qid"]]),
                    "batch_question_subject_id"     => $profile["subject_id"],
                    "batch_question_content"        => $profile["content"],
                    "batch_question_audio"          => $profile["audio"],
                    "batch_answer_combo_radio"      => $profile["type"] != Service_Data_Question::QUESTION_TYPE_RAIDO ? array() : $answer,
                    "batch_answer_combo_check"      => $profile["type"] != Service_Data_Question::QUESTION_TYPE_CHECK ? array() : $answer,
                    "batch_answer_combo_checkbox"   => $profile["type"] != Service_Data_Question::QUESTION_TYPE_CHECKBOX ? array() : $answer,
                    "batch_answer_combo_fill"       => $profile["type"] != Service_Data_Question::QUESTION_TYPE_FILL ? array() : $answer,
                    "batch_answer_explan_combo"     => array(),
                );
                if (!empty($profile["explan"])) {
                    $batchTmp["batch_answer_explan_combo"][] = array(
                        "batch_answer_explan" => $profile["explan"],
                    );
                }
                $question["batch_question_combo_item"][] = $batchTmp;
            }
        }

        return array(
            "question" => $question,
        );
    }
}