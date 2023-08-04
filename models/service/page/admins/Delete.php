<?php

class Service_Page_Admins_Delete extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkSuper()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $uid = empty($this->request['uid']) ? 0 : intval($this->request['uid']);
        if ($uid <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 需要选中管理员");
        }

        $serviceData = new Service_Data_Profile();
        $userInfo = $serviceData->getUserInfoByUid($uid);
        if (empty($userInfo)) {
            throw new Zy_Core_Exception(405, "操作失败, 管理员不存在");
        }

        $ret = $serviceData->deleteUserInfo($uid, $userInfo['type']);
        if ($ret == false) {
            throw new Zy_Core_Exception(405, "删除错误, 请重试");
        }
        return array();
    }
}