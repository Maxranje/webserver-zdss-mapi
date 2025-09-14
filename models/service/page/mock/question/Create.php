<?php

class Service_Page_Mock_Question_Create extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限操作");
        }

        $type       = empty($this->request['single_question_type']) ? 0 : intval($this->request['single_question_type']);
        $level      = empty($this->request['single_question_level']) ? 0 : intval($this->request['single_question_level']);
        $score      = empty($this->request['single_question_score']) ? 0 : intval($this->request['single_question_score']);
        $subjectId  = empty($this->request['single_question_subject_id']) ? 0 : intval($this->request['single_question_subject_id']);
        $tagIds     = empty($this->request['single_question_tag_ids']) ? array() : explode(",",$this->request['single_question_tag_ids']);
        $description= empty($this->request['single_question_description']) ? "" : trim($this->request['single_question_description']);
        $content    = empty($this->request['single_question_content']) ? "" : trim($this->request['single_question_content']);        
        $audio      = empty($this->request['single_question_audio']) ? "" : trim($this->request['single_question_audio']);
        $radio      = empty($this->request['single_answer_combo_radio']) ? array() : $this->request['single_answer_combo_radio'];
        $checkbox   = empty($this->request['single_answer_combo_checkbox']) ? array() : $this->request['single_answer_combo_checkbox'];
        $check      = empty($this->request['single_answer_combo_check']) ? array() : $this->request['single_answer_combo_check'];
        $fill       = empty($this->request['single_answer_combo_fill']) ? array() : $this->request['single_answer_combo_fill'];
        $meta       = empty($this->request['single_question_pre_meta_combo'][0]["single_question_pre_meta"]) ? "" : trim($this->request['single_question_pre_meta_combo'][0]["single_question_pre_meta"]);
        $explan     = empty($this->request['single_answer_explan_combo'][0]["single_answer_explan"]) ? "" : trim($this->request['single_answer_explan_combo'][0]["single_answer_explan"]);

        $reqParam = array(
            "questions" => array(
                array(
                    "type" => $type,
                    "level" => $level,
                    "score" => $score,
                    "subject_id" => $subjectId,
                    "tag_ids" => $tagIds,
                    "description" => $description,
                    "content" => $content,
                    "audio" => $audio,
                    "radio" => $radio,
                    "checkbox" => $checkbox,
                    "check" => $check,
                    "fill" => $fill,
                    "explan" => $explan,
                )
            ),
            "pre_meta" => array(
                "meta" => $meta, 
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