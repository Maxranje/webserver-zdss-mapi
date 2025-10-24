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
            return array();
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
                "type"=> "panel",
                "title"=> "",
                "className"=> "border-solid border-gray-100 shadow p-4 rounded-md",
                "body"=> array(),
            )            
        );
        // 物料
        if (!empty($questions[0]["pre_meta_id"])) {
            $serviceData = new Service_Data_Meta();
            $meta = $serviceData->getMetaById(intval($questions[0]["pre_meta_id"]));
            if (!empty($meta["content"])) {
                $result[0]["body"][] = array(
                    "type"=> "tpl",
                    "tpl" => "<p style='font-weight:900;'>前置材料</p>"
                );                
                if ($meta["meta_type"] == Service_Data_Meta::META_TYPE_AUDIO) {
                    $result[0]["body"][] = array(
                        "type"  => "audio",
                        "src"   =>$meta['content']
                    );
                } else {
                    $result[0]["body"][] = array(
                        "type"  => "html",
                        "html"   =>$meta['content']
                    );
                }                     
            }
        }

        $result[0]["body"][] = array(
            "type"=> "tpl",
            "tpl" => "<p style='font-weight:900;margin-top:2rem;'>题目详情</p>"
        ) ;            

        // 试题
        $questionsTpl = array(
            "type"=>  "tabs",
            "tabsMode"=> "simple",
            "className" => "mt-4",
            "defaultKey" => 0,           
            "tabs"=>  []
        );
        foreach ($questions as $index => $question) {
            if ($question["qid"] == $currentId) {
                $questionsTpl["defaultKey"] = $index;
            }  
            $tab = array(
                "title" => array(
                    "type" => "tpl",
                    "className"=> "font-black",
                    "tpl" => "Question " . $question["qid"],
                ),
                "tab" => array(
                    array(
                        "type" => "tpl",
                        "className" => "text-black font-black mt-2 block",
                        "tpl" => sprintf("%d. (%s) [%d分] %s", ($index+1), Service_Data_Question::QUESTION_TYPE_MAP_INFO[$question["type"]], $question["score"], $question["content"]),
                    ),
                )
            );

            $questionAnswer = empty($answers[$question['qid']]) ? array() : $answers[$question['qid']]; 
            // 试题答案
            if (!empty($questionAnswer) && in_array($question["type"], Service_Data_Question::QUESTION_TYPE_SIMPLE_MAP)) {   
                $answerHtml = "";
                $answerCorrect = array();
                foreach ($questionAnswer as $k => $v) {
                    $key = Service_Data_Question::QUESTION_PREFIX[$k];
                    if ($v["type"] == Service_Data_Question::QUESTION_TYPE_FILL) {
                        $key = $k+1;
                        if ($v["is_correct"] == 1) {
                            $answerCorrect[] = sprintf("%d: %s", $key, $v["content"]);
                        }                        
                    } else {
                        $answerHtml .= sprintf("<p style='padding: 12px; font-weight:500; border:1px solid #f3f4f6; background: #f3f4f6; border-radius: 0.25rem'>%s. %s</p>", $key, $v["content"]);                        
                        if ($v["is_correct"] == 1) {
                            $answerCorrect[] = $key;
                        }                        
                    }
                }
                $tab['tab'][] = array(
                    "type"=> "html",
                    "html"=> $answerHtml
                );
                $tab['tab'][] = array(
                    "type"=> "tpl",
                    "tpl" => "<p style='font-weight:900; margin-top:1rem;'>答案:</p>"
                ) ;                                
                $tab['tab'][] = array(
                    "type"=> "tpl",
                    "className" => "text-black text-md block",
                    "tpl"=> implode(", ", $answerCorrect)
                );                                   
            }
            $tab['tab'][] = array(
                "type"=> "tpl",
                "tpl" => "<p style='font-weight:900;margin-top:1rem;'>解析:</p>"
            ) ;            
            $tab['tab'][] = array(
                "type"  => "html",
                "className" => "text-black text-md block",
                "html"   => $question["explan"],
            ) ;

            $tab['tab'][] = array(
                "type"=> "divider"
            ) ;            

            $questionsTpl["tabs"][] = $tab;
        }

        $result[0]["body"][] = $questionsTpl;
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

        // 来源
        $serviceData = new Service_Data_QuestionSource();
        $qsMap = $serviceData->getSourceByQid($qids);        

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
            $question["single_question_source_ids"]     = empty($qsMap[$profile["qid"]]) ? array() : Zy_Helper_Utils::arrayInt($qsMap[$profile["qid"]]);
            $question["single_question_description"]    = $profile["description"];
            $question["single_question_content"]        = $profile["content"];
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
            $question["title"]                          = "Copy题目组:" . $parentId . " - 新建中";
            $question["defaultKey"]                     = 1;                
            $question["batch_question_combo_item"]      = array();
            $question["batch_question_pre_meta"]        = $meta["content"];
            $question["batch_question_pre_meta_type"]   = $meta["meta_type"];
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
                    "batch_question_source_ids"     => empty($qsMap[$profile["qid"]]) ? array() : Zy_Helper_Utils::arrayInt($qsMap[$profile["qid"]]),
                    "batch_question_content"        => $profile["content"],
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