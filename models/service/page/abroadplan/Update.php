<?php

class Service_Page_Abroadplan_Update extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $id         = empty($this->request['id']) ? 0 : intval($this->request['id']);
        $name       = empty($this->request['name']) ? "" : trim($this->request['name']);
        $price      = empty($this->request['price']) ? 0 : intval($this->request['price']) * 100;
        $duration   = empty($this->request['duration']) ? 0 : floatval($this->request['duration']);

        if (empty($name) || $price < 0 || $duration <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 请求参数错误");
        }

        $serviceData = new Service_Data_Abroadplan();
        $abroadplan = $serviceData->getAbroadplanById($id);
        if (empty($abroadplan)) {
            throw new Zy_Core_Exception(405, "操作失败, 留学计划不存在");
        }

        $abroadplanRecord = $serviceData->getRecordByConds(array("name" => $name));
        if (!empty($abroadplanRecord) && $abroadplanRecord['id'] != $id) {
            throw new Zy_Core_Exception(405, "操作失败, 留学计划名称重复");
        }

        $profile = [
            "name"          => $name, 
            "price"         => $price,
            "duration"      => floatval(sprintf("%.2f", $duration)),
            "update_time"   => time() , 
        ];

        $ret = $serviceData->update($id, $profile);
        if ($ret === false) {
            throw new Zy_Core_Exception(405, "更新失败, 请重试");
        }
        return array();
    }
}