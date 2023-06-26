<?php

class Service_Page_Admins_Delete extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkSuper()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $uid = empty($this->request['uid']) ? 0 : intval($this->request['uid']);
        if ($uid <= 0) {
            throw new Zy_Core_Exception(405, "需要选中管理员");
        }

        $serviceData = new Service_Data_User_Profile();
        $ret = $serviceData->deleteUserInfo($uid);
        if ($ret == false) {
            throw new Zy_Core_Exception(405, "删除错误, 请重试");
        }
        return array();
    }
}