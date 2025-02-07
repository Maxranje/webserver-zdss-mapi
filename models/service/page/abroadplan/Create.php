<?php

class Service_Page_Abroadplan_Create extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $name       = empty($this->request['name']) ? "" : trim($this->request['name']);
        $price      = empty($this->request['price']) ? 0 : intval($this->request['price']) * 100;
        $duration   = empty($this->request['duration']) ? 0 : floatval($this->request['duration']);

        if (empty($name) || $price < 0 || $duration <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 请求参数错误");
        }

        // 名称去重
        $serviceData = new Service_Data_Abroadplan();
        $conds = array(
            "name" => $name,
        );
        $count = $serviceData->getTotalByConds($conds);
        if ($count > 0) {
            throw new Zy_Core_Exception(405, "操作失败, 留学计划名称已存在请重新配置");
        }

        $profile = [
            "name"          => $name, 
            "price"         => $price,
            "duration"      => floatval(sprintf("%.2f", $duration)),
            "operator"      => OPERATOR,
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