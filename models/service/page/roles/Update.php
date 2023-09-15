<?php

class Service_Page_Roles_Update extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkSuper()) {
            throw new Zy_Core_Exception(405, "无权限");
        }

        $id         = empty($this->request['id']) ? 0 : intval($this->request['id']);
        $name       = empty($this->request['name']) ? "" : strval($this->request['name']);
        $descs      = empty($this->request['descs']) ? "" : strval($this->request['descs']);
        $pageIds    = empty($this->request['page_ids']) ? "" : strval($this->request['page_ids']);
        $modeIds    = empty($this->request['mode_ids']) ? "" : strval($this->request['mode_ids']);
        $uids       = empty($this->request['uids']) ? "" : strval($this->request['uids']);

        if ($id <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 请求参数错误, 请检查");
        }

        $pageIds = explode(",", $pageIds);
        $modeIds = explode(",", $modeIds);
        $newUids = explode(",", $uids);

        if (empty($name) || empty($pageIds) || empty($newUids)) {
            throw new Zy_Core_Exception(405, "操作失败, 名称或归属页面或用户uid不能为空, 如果要把角色去掉权限, 请删掉角色");
        }

        $serviceData = new Service_Data_Roles();
        $oldUids = $serviceData->getRolesMapById($id);
        $oldUids = array_column($oldUids, "uid");

        $diff2 = array_diff($newUids, $oldUids);
        $diff1 = array_diff($oldUids, $newUids);


        $ret = $serviceData->updateRoles($id, $name, $descs, $pageIds, $modeIds, $diff2, $diff1);
        if ($ret === false) {
            throw new Zy_Core_Exception(405, "更新失败, 请重试");
        }
        return array();
    }
}