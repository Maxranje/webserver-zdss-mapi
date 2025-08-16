<?php

class Service_Page_Mock_Paper_Details extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限操作");
        }

        $pid      = empty($this->request['pid']) ? 0 : intval($this->request['pid']);

        if ($pid <= 0) {
            return array();
        }

        return array(
            "question_labels" => array(
                array(
                    "title" => "第一题",
                    "sub_title" => "单选",
                    "value" => "12333",
                )
            ),
        );
   }
}