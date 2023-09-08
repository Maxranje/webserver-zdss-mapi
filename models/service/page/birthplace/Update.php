<?php

class Service_Page_Birthplace_Update extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkSuper()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $id         = empty($this->request['id']) ? 0 : intval($this->request['id']);
        $name       = empty($this->request['name']) ? "" : trim($this->request['name']);

        if ($id <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 需要选择某个生源地修改");
        }

        if (empty($name)) {
            throw new Zy_Core_Exception(405, "操作失败, 修改的生源地不能为空");
        }

        $serviceData = new Service_Data_Birthplace();
        $birthplace = $serviceData->getBirthplaceById($id);
        if (empty($birthplace)) {
            throw new Zy_Core_Exception(405, "操作失败, 修改的生源地信息不存在");
        }

        $birthplace = $serviceData->getBirthplaceByName($name);
        if (!empty($birthplace) && $birthplace['id'] != $id) {
            throw new Zy_Core_Exception(405, "操作失败, 生源地名称不能有重复");
        }

        $profile = [
            "name"          => $name, 
            "update_time"   => time() , 
        ];

        $ret = $serviceData->update($id, $profile);
        if ($ret === false) {
            throw new Zy_Core_Exception(405, "更新失败, 请重试");
        }
        return array();
    }
}