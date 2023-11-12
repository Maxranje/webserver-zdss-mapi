<?php

class Service_Page_Clasze_Create extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkSuper()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $name       = empty($this->request['name']) ? "" : trim($this->request['name']);
        $identify   = empty($this->request['identify']) ? "" : trim($this->request['identify']);

        if (empty($name) || empty($identify)) {
            throw new Zy_Core_Exception(405, "操作失败, 请求参数错误");
        }

        $serviceData = new Service_Data_Clasze();
        $clasze = $serviceData->getClaszeByName($name);
        if (!empty($clasze)) {
            throw new Zy_Core_Exception(405, "操作失败, 班型已存在");
        }

        $profile = [
            "name"          => $name, 
            "identify"      => $identify,
            "create_time"   => time() , 
            "update_time"   => time() , 
        ];

        $ret = $serviceData->create($profile);
        if ($ret == false) {
            throw new Zy_Core_Exception(405, "创建失败, 请重试");
        }
        return array();
    }
}