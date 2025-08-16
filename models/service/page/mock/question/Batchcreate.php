<?php

class Service_Page_Mock_Question_Batchcreate extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限操作");
        }
        return  array();
    }
}