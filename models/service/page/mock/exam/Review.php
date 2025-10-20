<?php

class Service_Page_Mock_Exam_Review extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $examId     = empty($this->request['exam_id']) ? 0  : intval($this->request['exam_id']);
        $studentUid = empty($this->request['student_uid']) ? 0  : intval($this->request['student_uid']);
        $isReview   = empty($this->request['is_review']) ? false : true;

        if ($examId <= 0 || $studentUid <= 0) {
            return array();
        }

        $serviceExam = new Service_Data_Exam();

        // 获取试卷信息
        $examInfo = $serviceExam->getExamById($examId);
        if (empty($examInfo["indentify"])) {
            throw new Zy_Core_Exception(405, "操作失败, 模考不存在");
        }

        // 获取考生考试信息
        $studentExam = $serviceExam->getStudentRecordByConds(array(
            "exam_id" => $examId,
            "student_uid" => $studentUid,
        ));
        if (empty($studentExam)) {
            throw new Zy_Core_Exception(405, "操作失败, 考生未参加模考");
        }
        if ($studentExam["status"] == Service_Data_Exam::EXAM_STUDENT_STATUS_PENDING) {
            throw new Zy_Core_Exception(405, "操作失败, 考生未开始考试");
        }
        $pid = intval($studentExam["pid"]);

        // 获取考生信息
        $serviceUser = new Service_Data_Profile();
        $studentInfo = $serviceUser->getUserInfoByUid($studentUid);
        if (empty($studentInfo["nickname"]) || $studentInfo["is_mock"] != Service_Data_Profile::STUDENT_MOCK) {
            throw new Zy_Core_Exception(405, "操作失败, 考生不存在或无权限");
        }

        // 获取试卷信息
        $servicePaper = new Service_Data_Paper();
        $paperInfo = $servicePaper->getPaperById($pid);
        if (empty($paperInfo["title"])) {
            throw new Zy_Core_Exception(405, "操作失败, 试卷不存在");
        }     

        // 获取所有试题和答案
        try {
            $ret = $serviceExam->getStudentAnswerDetailForReview($examId, $pid, $studentUid);
        } catch (Exception $e) {
            throw new Zy_Core_Exception(405, $e->getMessage() . ", 请重试");
        }

        $params = array(
            "exam_info" => $examInfo,
            "student_info" => $studentInfo,
            "paper_info" => $paperInfo,
            "student_exam" => $studentExam,
        );

        $panel = $this->format($ret, $params, $isReview);
        
        return $panel;
    }

    // 格式化
    public function format ($questionData, $params, $isReview) {
        $paperTypeInfo = $params["exam_info"]["paper_type"] == Service_Data_Paper::PAPER_TYPE_ASSESS ? "评估" : "常规";
        $totalQuestion = $params["exam_info"]["total_question"];
        $ret = array(
            array(
                "type" => "card",
                "className" => "border-solid border-gray-100 shadow p-4 rounded-md",
                "header" => array(
                    "className" => "bg-white",
                    "title" => $params["student_info"]["nickname"],
                    "titleClassName" => "text-bold text-black text-xl",
                    "subTitle" => "UID: " . $params["student_info"]["uid"],
                    "description" => "",
                    "avatarText" => strval($questionData["studentScore"]) . "分",
                    "avatarClassName" => "pull-left thumb avatar b-3x m-r",
                    "avatarTextBackground" => [
                        "#ed7011"
                    ]
                ),
                "body" => array(
                    "type" => "property",
                    "column" => 3,
                    "className" => "shadow-sm rounded bg-white",
                    "items" => [
                        array(
                            "label" => "试卷",
                            "content" => "<span class='text-black font-black ml-2'>" .$params["paper_info"]["title"]. "</span>",
                            "span" => 3
                        ),
                        array(
                            "label" => "总分",
                            "content" => "<span class='text-black font-black ml-2'>".$params["exam_info"]["total_score"]."</span>"
                        ),
                        array(
                            "label" => "最大题数",
                            "content" => "<span class='text-black font-black ml-2'>".$totalQuestion."</span>"
                        ),
                        array(
                            "label" => "类型",
                            "content" => "<span class='label label-danger ml-2 '>".$paperTypeInfo."</span>"
                        )
                    ]
                )      
            ),
            array(
                "type"=> "panel",
                "title"=> "",
                "className"=> "border-solid border-gray-100 shadow p-4 rounded-md",
                "body"=> array(),
            )
        );


        $ret[1]["body"] = $this->formatQuestionAnswerDetail($questionData, $isReview, $params["student_exam"]["status"]);
        return $ret;
    } 


    public function formatQuestionAnswerDetail ($questionData, $isReview, $params) {
        $outputArr = array();
        $lastMetaId = 0;
        $metas = array_column($questionData["metas"], null, "id");
        $studentExamStatus = empty($params["student_exam"]["status"]) ? 0 :$params["student_exam"]["status"];
        foreach ($questionData["questions"] as $j => $question) {
            // 没有作答的不展示
            if (empty($question["studentAnswer"])) {
                continue;
            }
            if ($question["metaId"] > 0 && $lastMetaId != $question["metaId"] && !empty($metas[$question["metaId"]])) {
                if ($metas[$question["metaId"]]["metaType"] == Service_Data_Meta::META_TYPE_AUDIO) {
                    $outputArr[] = array(
                        "type"  => "fieldSet",
                        "title" => "材料",
                        "className"=>"mt-2 mb-2 block",                        
                        "body"  => array(
                            array(
                                "type" => "audio",  
                                "src" => $metas[$question["metaId"]]["content"]
                            ),
                        )
                    );
                } else {
                    $outputArr[] = array(
                        "type"  => "fieldSet",
                        "title" => "材料",
                        "className"=>"mt-2 mb-2 block",                        
                        "body"  => array(
                            array(
                                "type" => "tpl",  
                                "tpl" => $metas[$question["metaId"]]["content"]
                            ),
                        )
                    );
                }
                $lastMetaId = $question["metaId"];
            }
            
            $panelBody = array(
                "type"  => "fieldSet",
                "title" => sprintf("%d. (%s) [%d分]", ($j+1), Service_Data_Question::QUESTION_TYPE_MAP_INFO[$question["type"]], $question["score"]),
                "body"  => array(
                    array(
                        "type" => "tpl",
                        "className" => "text-black text-lg",
                        "tpl" => $question["content"],
                    ),
                )
            );

            $questionAnswer = $question["questionAnswer"];
            $studentAnswer = $question["studentAnswer"];

            // 试题答案
            if (!empty($questionAnswer) && in_array($question["type"], Service_Data_Question::QUESTION_TYPE_SIMPLE_MAP)) {
                $studentAnswer = array_column($studentAnswer, null, "answerId");    
                $answerHtml = "";
                $answerCorrect = array();
                foreach ($questionAnswer as $k => $v) {
                    $key = Service_Data_Question::QUESTION_PREFIX[$k];
                    if ($v["type"] == Service_Data_Question::QUESTION_TYPE_FILL) {
                        $key = $k+1;
                        $content = empty($studentAnswer[$v["answerId"]]["answerContent"]) ? "" : $studentAnswer[$v["answerId"]]["answerContent"];
                        if (isset($studentAnswer[$v["answerId"]]) && $v["answerContent"] == $content) {
                            $answerHtml .= sprintf("<p style='padding: 12px; font-weight:500; border:1px solid #d1fae5; background: #d1fae5; border-radius: 0.25rem'>%s. %s</p>", $key, $content);
                        } else if (isset($studentAnswer[$v["answerId"]])) {
                            $answerHtml .= sprintf("<p style='padding: 12px; font-weight:500; border:1px solid #fecaca; background: #fecaca; border-radius: 0.25rem'>%s. %s</p>", $key, $content);
                        }

                        if ($v["isCorrect"] == 1) {
                            $answerCorrect[] = sprintf("%d: %s", $key, $v["answerContent"]);
                        }                        
                    } else {
                        if (isset($studentAnswer[$v["answerId"]]) && $v["isCorrect"] == 1) {
                            $answerHtml .= sprintf("<p style='padding: 12px; font-weight:500; border:1px solid #d1fae5; background: #d1fae5; border-radius: 0.25rem'>%s. %s</p>", $key, $v["answerContent"]);
                        } else if (isset($studentAnswer[$v["answerId"]]) && $v["isCorrect"] != 1) {
                            $answerHtml .= sprintf("<p style='padding: 12px; font-weight:500; border:1px solid #fecaca; background: #fecaca; border-radius: 0.25rem'>%s. %s</p>", $key, $v["answerContent"]);
                        } else {
                            $answerHtml .= sprintf("<p style='padding: 12px; font-weight:500; border:1px solid #f3f4f6; background: #f3f4f6; border-radius: 0.25rem'>%s. %s</p>", $key, $v["answerContent"]);
                        }                        
                        if ($v["isCorrect"] == 1) {
                            $answerCorrect[] = $key;
                        }                        
                    }
                }
                $panelBody["body"][] = array(
                    "type"=> "html",
                    "html"=> $answerHtml
                );
                $panelBody["body"][] = array(
                    "type"=> "tpl",
                    "className" => "text-black text-md font-bold block",
                    "tpl"=> "正确答案: "
                );                
                $panelBody["body"][] = array(
                    "type"=> "tpl",
                    "className" => "text-black text-md font-bold block",
                    "tpl"=> implode(", ", $answerCorrect)
                );                                   
            } else if ($question["type"] == Service_Data_Question::QUESTION_TYPE_SIMPLEWRITE || 
                $question["type"] == Service_Data_Question::QUESTION_TYPE_WRITE) { // 写作
                $content = empty($studentAnswer[0]["answerContent"]) ? "" : $studentAnswer[0]["answerContent"];                    
                $content = implode("<br/>", explode("\n", $content));
             
                $panelBody["body"][] = array(
                    "type"=> "tpl",
                    "className" => "text-black text-md font-bold block mt-2",
                    "tpl"=> "考生作答:"
                );
                $panelBody["body"][] = array(
                    "type"=> "html",
                    "className" => " mt-2",
                    "html"=> sprintf("<div style='padding: 12px; font-weight:500; border:1px solid #eff6ff; background: #eff6ff; border-radius: 0.25rem'>%s</div>", $content),
                );

                // 审批
                if ($isReview) {
                    $reviewContent = empty($studentAnswer[0]["reviewContent"]) ? "" : $studentAnswer[0]["reviewContent"];                    
                    $reviewContent = implode("<br/>", explode("\n", $reviewContent));      

                    $panelBody["body"][] = array(
                        "type"=> "tpl",
                        "className" => "text-black text-md font-bold block mt-2",
                        "tpl"=> "老师批改:"
                    );     
                    if  (!empty($reviewContent) || !empty($question["studentAnswer"][0]["score"])) {
                        $panelBody["body"][] = array(
                            "type"=> "html",
                            "className" => " mt-2",
                            "html"=> sprintf("<div style='padding: 12px; font-weight:500; border:1px solid #eff6ff; background: #eff6ff; border-radius: 0.25rem'><div>给分: %d分 %s</p>详情: <div>%s</div></div>", 
                                empty($question["studentAnswer"][0]["score"]) ? 0 : $question["studentAnswer"][0]["score"],
                                empty($question["studentAnswer"][0]["isAI"]) ? "" : $question["studentAnswer"][0]["isAI"],
                                $reviewContent),
                        );                          
                    }                                 
                    if (in_array($studentExamStatus, [
                        Service_Data_Exam::EXAM_STUDENT_STATUS_COMPLETE,
                        Service_Data_Exam::EXAM_STUDENT_STATUS_REVIEWING,
                        Service_Data_Exam::EXAM_STUDENT_STATUS_TERMINATED,
                    ])) {
                        $panelBody["body"][] = array_merge($panelBody["body"], $this->getReviewFrom($question, $params, $reviewContent));
                    }                               
                }
            } else if ($question["type"] == Service_Data_Question::QUESTION_TYPE_SPEAK) {
                $content = empty($studentAnswer[0]["answerContent"]) ? "" : $studentAnswer[0]["answerContent"];  

                $panelBody["body"][] = array(
                    "type"=> "tpl",
                    "className" => "text-black text-md font-bold block mt-2",
                    "tpl"=> "考生作答:"
                );
                $panelBody["body"][] = array(
                    "type"=> "audio",
                    "className" => " mt-2",
                    "src"=> $content,
                );
                if ($isReview) {
                    $reviewContent = empty($studentAnswer[0]["reviewContent"]) ? "" : $studentAnswer[0]["reviewContent"];                    
                    $reviewContent = implode("<br/>", explode("\n", $reviewContent));                        
                    $panelBody["body"][] = array(
                        "type"=> "tpl",
                        "className" => "text-black text-md font-bold block mt-2",
                        "tpl"=> "老师评语:"
                    );   
                    if  (!empty($reviewContent) || !empty($question["studentAnswer"][0]["score"])) {
                        $panelBody["body"][] = array(
                            "type"=> "html",
                            "className" => " mt-2",
                            "html"=> sprintf("<div style='padding: 12px; font-weight:500; border:1px solid #eff6ff; background: #eff6ff; border-radius: 0.25rem'><div>给分: %d分 %s</p>详情: <div>%s</div></div>", 
                                empty($question["studentAnswer"][0]["score"]) ? 0 : $question["studentAnswer"][0]["score"],
                                empty($question["studentAnswer"][0]["isAI"]) ? "" : $question["studentAnswer"][0]["isAI"],
                                $reviewContent),
                        );                          
                    }                                    
                    if (in_array($studentExamStatus, [
                        Service_Data_Exam::EXAM_STUDENT_STATUS_COMPLETE,
                        Service_Data_Exam::EXAM_STUDENT_STATUS_REVIEWING,
                        Service_Data_Exam::EXAM_STUDENT_STATUS_TERMINATED,
                    ])) {
                        $panelBody["body"][] = array_merge($panelBody["body"], $this->getReviewFrom($question, $params, $reviewContent));
                    }
                }
            }
            
            $panelBody["body"][] = array(
                "type"=> "tpl",
                "className" => "text-black text-md font-bold block mt-2",
                "tpl"=> "解析:"
            );                
            $panelBody["body"][] = array(
                "type"  => "html",
                "className" => "text-black text-md font-bold block",
                "html"   => $question["explan"],
            ) ;

            $panelBody["body"][] = array(
                "type"=> "divider"
            ) ;            

            $outputArr[] = $panelBody;
        }

        // 没有作答
        if (empty($outputArr)) {
            $outputArr[] = array(
                "type"=> "tpl",
                "className" => "text-black text-md font-bold block mt-2 text-center",
                "tpl"=> "暂未作答"
            );
        }    
        return $outputArr;    
    }    

    // review
    private function getReviewFrom ($question, $params, $content) {
        return array(
            "type" => "wrapper",
            "className" => "relative p-0",
            "body" => array(
                array(
                    "type"=> "button",
                    "level" => "success",
                    "icon"=> "fa fa-plus-circle",
                    "className"=> "border-solid mb-1 mt-2 inline-block rounded",
                    "label"=> "AI生成批改",        
                    "onEvent" => array(
                        "click" => array(
                            "actions" => array(
                                array(
                                    "actionType" => "setValue",
                                    "componentId" => "review_ai_" . $question["qid"],
                                    "args" => array(
                                        "value" => '2',
                                    ),
                                ),                              
                                array(
                                    "actionType" => "ajax",
                                    "api"=> array(
                                        "method"=> "post",
                                        "url"=> "/mapi/mock/review_getai",
                                        "dataType"=> "form",
                                        "data" => array(
                                            "exam_id" => $params["exam_info"]["id"],
                                            "qid" => $question["qid"],
                                            "student_uid" => $params["student_info"]["uid"],
                                        ),
                                    ),                      
                                ),                     
                                array(
                                    "actionType" => "setValue",
                                    "componentId" => "review_content_" . $question["qid"],
                                    "args" => array(
                                        "value" => '${responseResult.review_content || ""}',
                                    ),
                                ), 
                                array(
                                    "actionType" => "setValue",
                                    "componentId" => "review_score_" . $question["qid"],
                                    "args" => array(
                                        "value" => '${responseResult.review_score || ""}',
                                    ),
                                ),                               
                                array(
                                    "actionType" => "setValue",
                                    "componentId" => "review_ai_" . $question["qid"],
                                    "args" => array(
                                        "value" => '1',
                                    ),
                                ),                                             
                            )
                        ),
                    )
                ),
                array(
                    "type"=> "form",
                    "api"=> array(
                        "method"=> "post",
                        "url"=> "/mapi/mock/review_save",
                        "dataType"=> "form"
                    ),
                    "onEvent"=>array(
                        "submitSucc"=>array(
                            "actions"=>array(
                                array(
                                    "actionType"=>"rebuild",
                                    "componentId"=>"review_page_detail"
                                ),
                            )
                        )
                    ),                   
                    "title"=> "",
                    "wrapWithPanel"=> false,
                    "className"=> "border-solid border-gray-200 p-4 rounded p-0 mt-2",
                    "body"=> array(                                   
                        array(
                            "type" => "input-text",
                            "name" => "exam_id",
                            "value" => $params["exam_info"]["id"],
                            "hidden" => true
                        ),  
                        array(
                            "type" => "input-text",
                            "name" => "qid",
                            "value" => $question["qid"],
                            "hidden" => true
                        ),                  
                        array(
                            "type" => "input-text",
                            "name" => "student_uid",
                            "value" => $params["student_info"]["uid"],
                            "hidden" => true
                        ),                                                             
                        array(
                            "type" => "input-number",
                            "name"=>  "review_score_" . $question["qid"],
                            "id"=>  "review_score_" . $question["qid"],
                            "max" => $question["score"],
                            "value" => empty($question["studentAnswer"][0]["score"]) ? 0 : $question["studentAnswer"][0]["score"],
                            "desc" => "批改分数, 0到当前题最大分值",
                        ),
                        array(
                            "type"=>  "input-rich-text",
                            "name"=>  "review_content_" . $question["qid"],
                            "id"=>  "review_content_" . $question["qid"],
                            "required"=>  true,
                            "value" => $content,
                            "options"=>  array(
                                "menubar"=>  false,
                                "height"=>  200,
                                "resize"=>  true,
                                "content_css"=>  "/public/mis/sdk/tinymce-custom.css",
                                "plugins"=>  ["advlist","autolink","link","image","lists","charmap","preview","anchor","pagebreak","searchreplace","wordcount","visualblocks","visualchars","code","fullscreen","insertdatetime","media","nonbreaking","table","emoticons","template","help"],
                                "toolbar" => "formatselect | bold italic backcolor  | alignleft aligncenter alignright | bullist numlist outdent indent | removeformat"
                            )        
                        ),                                             
                        array(
                            "type"=> "button",
                            "actionType"=> "submit",
                            "level" => "info",
                            "className"=> "border-solid block rounded",
                            "label"=> "提交批改",
                        ),   
                        array(
                            "type"=> "hidden",
                            "value"=> 0,
                            "name" => "review_ai_" . $question["qid"],
                            "id" => "review_ai_" . $question["qid"],
                        ),                                                                  
                    ) 
                ),
                array(
                    "type"=> "hidden",
                    "value"=> 0,
                    "name" => "review_ai_" . $question["qid"],
                    "id" => "review_ai_" . $question["qid"],
                ),                    
                array(
                    "type"=> "spinner",
                    'size' => "lg",
                    "id" => "spinner_" . $question["qid"],
                    "name" => "spinner_" . $question["qid"],
                    "overlay" => true,
                    "showOn" => '${review_ai_'.$question['qid'].' == 2}',
                    "className" => "absolute z-40" ,
                ),   
            )
        );      
    }
}