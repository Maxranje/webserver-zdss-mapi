<?php

class Service_Page_Mock_Paper_Create extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限操作");
        }

        $title          = empty($this->request['title']) ? "" : trim($this->request['title']);
        $type           = empty($this->request['type']) ? 0 : intval($this->request['type']);
        $remark         = empty($this->request['remark']) ? "" : trim($this->request['remark']);

        if (empty($title) || !Zy_Helper_Utils::validateString($title, 1, 50)) {
            throw new Zy_Core_Exception(405, "操作失败, 标题必填且长度50个字内");
        }        

        if (!empty($remark) && !Zy_Helper_Utils::validateString($remark, 1, 100)) {
            throw new Zy_Core_Exception(405, "操作失败, 备注长度100个字内");
        }

        if (!in_array($type, Service_Data_Paper::PAPER_TYPE_MAP)) {
            throw new Zy_Core_Exception(405, "操作失败, 试卷类型不正确");
        }

        $profile = array(
            "title"         => $title,
            "remark"        => $remark,
            "type"          => $type,
        );

        $serviceData = new Service_Data_Paper();
        $ret = $serviceData->create($profile);        
        if ($ret == false) {
            throw new Zy_Core_Exception(405, "创建失败, 请重试");
        }
        return array();
    }
}