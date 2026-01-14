<?php

class Service_Page_Mock_Question_Createbatch extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限操作");
        }

        $meta           = empty($this->request['batch_question_pre_meta']) ? "" : trim($this->request['batch_question_pre_meta']);
        $metaType       = empty($this->request['batch_question_pre_meta_type']) ? 1 : intval($this->request['batch_question_pre_meta_type']);
        $description    = empty($this->request['batch_question_description']) ? "" : trim($this->request['batch_question_description']);
        $reqItems       = empty($this->request["batch_question_combo_item"]) ? array() : $this->request["batch_question_combo_item"];

        if (empty($meta) || empty($reqItems) || empty($description) || !in_array($metaType, Service_Data_Meta::META_TYPE_MAP)) {
            throw new Zy_Core_Exception(405, "操作失败, 请求参数错误");
        }

        if ($metaType == Service_Data_Meta::META_TYPE_AUDIO) {
            $meta = strip_tags($meta);
        }

        // 对内容在格式化一下
        $reqParam = array(
            "questions" => array(),
            "pre_meta" => array(
                "meta" => $meta,
                "meta_type" => $metaType,
                "parent_desc" => $description,
                "is_group" => true,   
            ),
        );
        foreach ($reqItems as $item) {
            $tmp = array(
                "type"          => empty($item['batch_question_type']) ? 0 : intval($item['batch_question_type']),
                "level"         => empty($item['batch_question_level']) ? 0 : intval($item['batch_question_level']),
                "score"         => empty($item['batch_question_score']) ? 0 : intval($item['batch_question_score']),
                "source_ids"    => empty($item['batch_question_source_ids']) ? array() : $item['batch_question_source_ids'],
                "tag_ids"       => empty($item['batch_question_tag_ids']) ? array() : $item['batch_question_tag_ids'],
                "description"   => $description,
                "content"       => empty($item['batch_question_content']) ? "" : trim($item['batch_question_content']),        
                "radio"         => empty($item['batch_answer_combo_radio']) ? array() : $item['batch_answer_combo_radio'],
                "checkbox"      => empty($item['batch_answer_combo_checkbox']) ? array() : $item['batch_answer_combo_checkbox'],
                "check"         => empty($item['batch_answer_combo_check']) ? array() : $item['batch_answer_combo_check'],
                "fill"          => empty($item['batch_answer_combo_fill']) ? array() : $item['batch_answer_combo_fill'],
                "explan"        => empty($item['single_answer_explan_combo'][0]["single_answer_explan"]) ? "" : trim($item['single_answer_explan_combo'][0]["single_answer_explan"]),
            );
            if (!empty($tmp["tag_ids"]) && (is_string($tmp["tag_ids"]) || is_array($tmp["tag_ids"]))) {
                if (is_string($tmp["tag_ids"])) {
                    $tmp["tag_ids"] = explode(",", $tmp["tag_ids"]);
                } 
                $tmp["tag_ids"] = Zy_Helper_Utils::rmArrZore(Zy_Helper_Utils::arrayInt($tmp["tag_ids"]));
            }

            if (!empty($tmp["source_ids"]) && (is_string($tmp["source_ids"]) || is_array($tmp["source_ids"]))) {
                if (is_string($tmp["source_ids"])) {
                    $tmp["source_ids"] = explode(",", $tmp["source_ids"]);
                } 
                $tmp["source_ids"] = Zy_Helper_Utils::rmArrZore(Zy_Helper_Utils::arrayInt($tmp["source_ids"]));
            }  
            $reqParam["questions"][] = $tmp;            
        }

        $serviceData = new Service_Data_Question();
        // 参数校验
        $profile = $serviceData->checkReqQuestionParam ($reqParam);
        if (empty($profile)) {
            throw new Zy_Core_Exception(405, "操作失败, 提交格式不正确, 请检查重试");
        }
        // 创建
        $ret = $serviceData->createBatch(array($profile));
        if ($ret == false) {
            throw new Zy_Core_Exception(405, "批量创建失败, 请重试");
        }
        return array();
    }
}