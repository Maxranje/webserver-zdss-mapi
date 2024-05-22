<?php

class Service_Page_Plan_Create extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $name = empty($this->request['name']) ? "" : trim($this->request['name']);
        $price = empty($this->request['price']) ? 0 : intval($this->request['price']) * 100;

        if (empty($name) || $price < 0) {
            throw new Zy_Core_Exception(405, "操作失败, 请求参数错误");
        }

        $serviceData = new Service_Data_Plan();
        $Plan = $serviceData->getPlanByName($name);
        if (!empty($Plan)) {
            throw new Zy_Core_Exception(405, "操作失败, 计划已存在");
        }

        $profile = [
            "name"          => $name, 
            "price"         => $price,
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