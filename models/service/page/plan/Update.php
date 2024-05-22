<?php

class Service_Page_Plan_Update extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $id         = empty($this->request['id']) ? 0 : intval($this->request['id']);
        $name       = empty($this->request['name']) ? "" : trim($this->request['name']);
        $price = empty($this->request['price']) ? 0 : intval($this->request['price']) * 100;

        if (empty($name) || $price < 0) {
            throw new Zy_Core_Exception(405, "操作失败, 请求参数错误");
        }

        $serviceData = new Service_Data_Plan();
        $Plan = $serviceData->getPlanById($id);
        if (empty($Plan)) {
            throw new Zy_Core_Exception(405, "操作失败, 计划不存在");
        }

        $Plan = $serviceData->getPlanByName($name);
        if (!empty($Plan) && $Plan['id'] != $id) {
            throw new Zy_Core_Exception(405, "操作失败, 计划名称重复");
        }

        $profile = [
            "name"          => $name, 
            "price"         => $price,
            "update_time"   => time() , 
        ];

        $ret = $serviceData->update($id, $profile);
        if ($ret === false) {
            throw new Zy_Core_Exception(405, "更新失败, 请重试");
        }
        return array();
    }
}