<?php

class Service_Page_Mock_Question_Createimport extends Zy_Core_Service{

    private $serviceQuestion;

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限操作");
        }

        $questionFile  = empty($this->request['question_file']) ? array() : $this->request['question_file'];
        if (empty($questionFile) || count($questionFile) <= 1) {
            throw new Zy_Core_Exception(405, "操作失败, 上传文件格式不正确, 请于系统提供对照");
        }

        $this->serviceQuestion = new Service_Data_Question();
        // 参数校验
        $reqParam = $this->checkReqQuestionParam ($questionFile);
        if (empty($reqParam)) {
            throw new Zy_Core_Exception(405, "操作失败, 上传文件格式不正确, 请重新提交");
        }
        // 创建
        $ret = $this->serviceQuestion->createBatch($reqParam);
        if ($ret == false) {
            throw new Zy_Core_Exception(405, "创建失败, 请重试");
        }
        return array();
    }

    private function checkReqQuestionParam ($request) {
        unset($request[0]);

        // 对物料处理
        $preMeta = array();    
        foreach ($request as $k => $v) {
            $groupKey           = empty($v[0]) ? 0 : $v[0];
            $description        = empty($v[1]) ? "" : $v[1];
            $meta               = empty($v[2]) ? "" : $v[2];

            if (!empty($groupKey)) {
                if (!isset($preMeta[$groupKey])) {
                    $preMeta[$groupKey] = array(
                        "meta" => "", 
                        "is_group" => true, 
                    );
                }
                // 有材料, 只填充第一个
                if (!empty($meta) && empty($preMeta[$groupKey]['meta'])) {
                    $preMeta[$groupKey]["meta"] = $meta;
                    $preMeta[$groupKey]["parent_desc"] = $description;
                }
            } else {
                $groupKey = !empty($meta) ? "pre_meta_" . $k : "none_" . $k;
                $preMeta[$groupKey] = array(
                    "meta" => $meta,
                    "is_group" => false, 
                );
            }
            $request[$k][0] = $groupKey;
        }

        // 对question处理
        $questions = array();
        foreach($request as $k => $v) {
            $q = array(
                "description" => empty($v[1]) ? "" : $v[1],
                "type" => empty($v[3]) ? 0 : intval($v[3]),
                "level" => empty($v[4]) ? 0 : intval($v[4]),
                "score" => empty($v[5]) ? 0 : intval($v[5]),
                "tag_ids" => empty($v[6]) ? array() : Zy_Helper_Utils::arrayInt(explode(",", $v[6])),
                "subject_id" => empty($v[7]) ? 0 : intval($v[7]),
                "audio" => empty($v[8]) ? "" : $v[8],
                "content" => empty($v[9]) ? "" : $v[9],
                "explan" => empty($v[12]) ? "" : $v[12],
                "radio" => array(),
                "checkbox" => array(),
                "check" => array(),
                "fill" => array(),
            );
            $groupKey = $v[0];
            $answer = empty($v[10]) ? "" : $v[10];
            $correct = empty($v[11]) ? array() : Zy_Helper_Utils::arrayInt(explode(",", $v[11]));

            if (!empty($preMeta[$groupKey]['parent_desc'])) {
                $q["description"] = $preMeta[$groupKey]['parent_desc'];
            }

            // 单选/多选/填空/判断 处理
            if (in_array($q['type'],  [
                    Service_Data_Question::QUESTION_TYPE_CHECKBOX,
                    Service_Data_Question::QUESTION_TYPE_CHECK,
                    Service_Data_Question::QUESTION_TYPE_RAIDO,
                    Service_Data_Question::QUESTION_TYPE_FILL]) && 
                !empty($answer)) {
                
                $key = "radio";
                switch($q["type"]) {
                    case Service_Data_Question::QUESTION_TYPE_CHECKBOX :
                        $key = "checkbox";
                        break;
                    case Service_Data_Question::QUESTION_TYPE_RAIDO :
                        $key = "radio";
                        break;
                    case Service_Data_Question::QUESTION_TYPE_CHECK :
                        $key = "check";
                        break;
                    case Service_Data_Question::QUESTION_TYPE_FILL :
                        $key = "fill";
                        break;                        
                    default : 
                        $key = "radio";
                        break;
                }
                    
                $q[$key] = array_map(function($item) {
                    return ["answer_content" => $item];
                }, explode("\n", $answer));

                // 填空没有对错
                if ($q["type"] != Service_Data_Question::QUESTION_TYPE_FILL) {
                    foreach ($correct as $v) {
                        if ($v <= 0 || !isset($q[$key][$v - 1])) {
                            continue;
                        }
                        $q[$key][$v - 1]["is_answer"] = 1;
                    }
                }
            }
            $questions[$groupKey][] = $q;
        }

        // 把question数组和 meta关联在一起,  发过去
        $reqParam = array();
        foreach ($questions as $key => $q) {
            if (empty($q)) {
                continue;
            }
            if (empty($preMeta[$key])) {
                continue;
            }
            $tmp = array(
                "questions" => $q,
                "pre_meta" => $preMeta[$key],
            );
            $reqParam[] = $this->serviceQuestion->checkReqQuestionParam($tmp);
        }
        return $reqParam;
    }
}