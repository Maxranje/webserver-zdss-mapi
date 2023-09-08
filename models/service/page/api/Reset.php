<?php

class Service_Page_Api_Reset extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $uid  = empty($this->request['uid']) ? 0 : intval($this->request['uid']);
        if ($uid <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 请选择用户");
        }

        $serviceData = new Service_Data_Profile();
        $userInfo = $serviceData->getUserInfoByUid($uid);
        if (empty($userInfo) || empty($userInfo['phone'])) {
            throw new Zy_Core_Exception(405, "操作失败, 用户不存在或手机号为空");
        }

        $profile = array(
            'passport' => $userInfo['phone'],
        );

        $ret = $serviceData->editUserInfo($uid, $profile);
        if ($ret == false) {
            throw new Zy_Core_Exception(405, "重置错误, 请重试");
        }
        
        return array();
    }
}