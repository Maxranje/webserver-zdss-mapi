<?php

class Service_Page_Clasze_Update extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkSuper()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $id         = empty($this->request['id']) ? 0 : intval($this->request['id']);
        $name       = empty($this->request['name']) ? "" : trim($this->request['name']);
        $identify   = empty($this->request['identify']) ? "" : trim($this->request['identify']);

        if ($id <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 需要选择某个班型修改");
        }

        if (empty($name) || empty($identify)) {
            throw new Zy_Core_Exception(405, "操作失败, 修改班型名称或标识不能为空");
        }

        $serviceData = new Service_Data_Clasze();
        $Clasze = $serviceData->getClaszeById($id);
        if (empty($Clasze)) {
            throw new Zy_Core_Exception(405, "操作失败, 修改的班型信息不存在");
        }

        $Clasze = $serviceData->getClaszeByName($name);
        if (!empty($Clasze) && $Clasze['id'] != $id) {
            throw new Zy_Core_Exception(405, "操作失败, 班型名称不能有重复");
        }

        $profile = [
            "name"          => $name, 
            "identify"      => $identify,
            "update_time"   => time() , 
        ];

        $ret = $serviceData->update($id, $profile);
        if ($ret === false) {
            throw new Zy_Core_Exception(405, "更新失败, 请重试");
        }
        return array();
    }
}