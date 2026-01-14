<?php

class Service_Page_Mock_Question_Create extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限操作");
        }

        $type       = empty($this->request['single_question_type']) ? 0 : intval($this->request['single_question_type']);
        $level      = empty($this->request['single_question_level']) ? 0 : intval($this->request['single_question_level']);
        $score      = empty($this->request['single_question_score']) ? 0 : intval($this->request['single_question_score']);
        $sourceIds  = empty($this->request['single_question_source_ids']) ? array() : $this->request['single_question_source_ids'];
        $tagIds     = empty($this->request['single_question_tag_ids']) ? array() : $this->request['single_question_tag_ids'];
        $description= empty($this->request['single_question_description']) ? "" : trim($this->request['single_question_description']);
        $content    = empty($this->request['single_question_content']) ? "" : trim($this->request['single_question_content']);        
        $radio      = empty($this->request['single_answer_combo_radio']) ? array() : $this->request['single_answer_combo_radio'];
        $checkbox   = empty($this->request['single_answer_combo_checkbox']) ? array() : $this->request['single_answer_combo_checkbox'];
        $check      = empty($this->request['single_answer_combo_check']) ? array() : $this->request['single_answer_combo_check'];
        $fill       = empty($this->request['single_answer_combo_fill']) ? array() : $this->request['single_answer_combo_fill'];
        $meta       = empty($this->request['single_question_pre_meta_combo'][0]["single_question_pre_meta"]) ? "" : trim($this->request['single_question_pre_meta_combo'][0]["single_question_pre_meta"]);
        $explan     = empty($this->request['single_answer_explan_combo'][0]["single_answer_explan"]) ? "" : trim($this->request['single_answer_explan_combo'][0]["single_answer_explan"]);

        if (!empty($tagIds) && (is_string($tagIds) || is_array($tagIds))) {
            if (is_string($tagIds)) {
                $tagIds = explode(",", $tagIds);
            } 
            $tagIds = Zy_Helper_Utils::rmArrZore(Zy_Helper_Utils::arrayInt($tagIds));
        }

        if (!empty($sourceIds) && (is_string($sourceIds) || is_array($sourceIds))) {
            if (is_string($sourceIds)) {
                $sourceIds = explode(",", $sourceIds);
            } 
            $sourceIds = Zy_Helper_Utils::rmArrZore(Zy_Helper_Utils::arrayInt($sourceIds));
        }        

        $reqParam = array(
            "questions" => array(
                array(
                    "type" => $type,
                    "level" => $level,
                    "score" => $score,
                    "source_ids" => $sourceIds,
                    "tag_ids" => $tagIds,
                    "description" => $description,
                    "content" => $content,
                    "radio" => $radio,
                    "checkbox" => $checkbox,
                    "check" => $check,
                    "fill" => $fill,
                    "explan" => $explan,
                )
            ),
            "pre_meta" => array(
                "meta" => $meta, 
                "meta_type" => 1,
                "is_group" => false, 
            ),
        );

        $serviceData = new Service_Data_Question();
        // 参数校验
        $profile = $serviceData->checkReqQuestionParam ($reqParam);
        if (empty($profile)) {
            throw new Zy_Core_Exception(405, "操作失败, 提交格式不正确, 请检查重试");
        }

        // 创建
        $ret = $serviceData->createBatch(array($profile));
        if ($ret == false) {
            throw new Zy_Core_Exception(405, "创建失败, 请重试");
        }
        return array();
    }
}