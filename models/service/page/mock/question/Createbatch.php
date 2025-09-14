<?php

class Service_Page_Mock_Question_Createbatch extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限操作");
        }

        $meta           = empty($this->request['batch_question_pre_meta']) ? "" : trim($this->request['batch_question_pre_meta']);
        $description    = empty($this->request['batch_question_description']) ? "" : trim($this->request['batch_question_description']);
        $reqItems       = empty($this->request["batch_question_combo_item"]) ? array() : $this->request["batch_question_combo_item"];

        if (empty($meta) || empty($reqItems) || empty($description)) {
            throw new Zy_Core_Exception(405, "操作失败, 请求参数错误");
        }

        // 对内容在格式化一下
        $reqParam = array(
            "questions" => array(),
            "pre_meta" => array(
                "meta" => $meta,
                "parent_desc" => $description,
                "is_group" => true,   
            ),
        );
        foreach ($reqItems as $item) {
            $reqParam["questions"][] = array(
                "type"          => empty($item['batch_question_type']) ? 0 : intval($item['batch_question_type']),
                "level"         => empty($item['batch_question_level']) ? 0 : intval($item['batch_question_level']),
                "score"         => empty($item['batch_question_score']) ? 0 : intval($item['batch_question_score']),
                "subject_id"    => empty($item['batch_question_subject_id']) ? 0 : intval($item['batch_question_subject_id']),
                "tag_ids"       => empty($item['batch_question_tag_ids']) ? array() : explode(",",$item['batch_question_tag_ids']),
                "description"   => $description,
                "content"       => empty($item['batch_question_content']) ? "" : trim($item['batch_question_content']),        
                "audio"         => empty($item['batch_question_audio']) ? "" : trim($item['batch_question_audio']),
                "radio"         => empty($item['batch_answer_combo_radio']) ? array() : $item['batch_answer_combo_radio'],
                "checkbox"      => empty($item['batch_answer_combo_checkbox']) ? array() : $item['batch_answer_combo_checkbox'],
                "check"         => empty($item['batch_answer_combo_check']) ? array() : $item['batch_answer_combo_check'],
                "fill"          => empty($item['batch_answer_combo_fill']) ? array() : $item['batch_answer_combo_fill'],
                "explan"        => empty($item['single_answer_explan_combo'][0]["single_answer_explan"]) ? "" : trim($item['single_answer_explan_combo'][0]["single_answer_explan"]),
            );
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