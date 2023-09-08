<?php

class Service_Page_Birthplace_Create extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkSuper()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $name = empty($this->request['name']) ? "" : trim($this->request['name']);

        if (empty($name)) {
            throw new Zy_Core_Exception(405, "操作失败, 请求参数错误");
        }

        $serviceData = new Service_Data_Birthplace();
        $birthplace = $serviceData->getBirthplaceByName($name);
        if (!empty($birthplace)) {
            throw new Zy_Core_Exception(405, "操作失败, 生源地已存在");
        }

        $profile = [
            "name"          => $name, 
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