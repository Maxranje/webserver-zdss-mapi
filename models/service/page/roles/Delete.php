<?php

class Service_Page_Roles_Delete extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkSuper()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $id = empty($this->request['id']) ? 0 : intval($this->request['id']);

        if ($id <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 要删除的角色id不存在");
        }

        $serviceData = new Service_Data_Roles();
        $roles = $serviceData->getRolesById($id);
        if (empty($roles)) {
            throw new Zy_Core_Exception(405, "操作失败, 要删除的角色不存在");
        }

        $ret = $serviceData->deleteRoles($id);
        if ($ret == false) {
            throw new Zy_Core_Exception(405, "删除失败, 请重试");
        }
        return array();
    }
}