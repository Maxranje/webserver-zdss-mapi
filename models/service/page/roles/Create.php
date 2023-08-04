<?php

class Service_Page_Roles_Create extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkSuper()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $name = empty($this->request['name']) ? "" : strval($this->request['name']);
        $descs = empty($this->request['descs']) ? "" : strval($this->request['descs']);

        if (empty($name)) {
            throw new Zy_Core_Exception(405, "操作失败, 角色名称不能为空");
        }

        $serviceData = new Service_Data_Roles();
        $roles = $serviceData->getRolesByName($name);
        if (!empty($roles)) {
            throw new Zy_Core_Exception(405, "操作失败, 角色名字不能有重复");
        }

        $profile = [
            "name"          => $name, 
            "page_ids"      => "", 
            "descs"         => $descs, 
            "create_time"   => time() , 
            "update_time"   => time() , 
        ];

        $ret = $serviceData->createRoles($profile);
        if ($ret == false) {
            throw new Zy_Core_Exception(405, "创建失败, 请重试");
        }
        return array();
    }
}