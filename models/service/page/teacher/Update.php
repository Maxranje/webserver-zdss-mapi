<?php

class Service_Page_Teacher_Update extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $uid        = empty($this->request['uid']) ? 0 : intval($this->request['uid']);
        $name       = empty($this->request['name']) ? "" : trim($this->request['name']);
        $phone      = empty($this->request['phone']) ? "" : trim($this->request['phone']);
        $nickname   = empty($this->request['nickname']) ? "" : trim($this->request['nickname']);
        $sex        = empty($this->request['sex']) ? "M" : trim($this->request['sex']);
        $state      = empty($this->request['state']) ? 0 : intval($this->request['state']);

        if (empty($name) || empty($nickname) || empty($phone) || !in_array($sex, ['M', "F"]) || !in_array($state, [1,2])) {
            throw new Zy_Core_Exception(405, "操作失败, 部分参数为空或非法, 请检查");
        }

        $serviceData = new Service_Data_Profile();
        $userInfo = $serviceData->getUserInfoByUid($uid);
        if (empty($userInfo)) {
            throw new Zy_Core_Exception(405, "操作失败, 无法查到相关用户");
        }

        $userInfo = $serviceData->getUserInfoByNameAndPhone($name, $phone);
        if (!empty($userInfo) && $userInfo['uid'] != $uid) {
            throw new Zy_Core_Exception(405, "操作失败, 用户名/手机号关联的账户已存在");
        }

        $profile = [
            "type"          => Service_Data_Profile::USER_TYPE_TEACHER, 
            "name"          => $name , 
            "nickname"      => $nickname , 
            "phone"         => $phone, 
            "avatar"        => "",
            "sex"           => $sex, 
            "state"         => $state,
            "update_time"  => time() , 
        ];

        $ret = $serviceData->editUserInfo($uid, $profile);
        if ($ret === false) {
            throw new Zy_Core_Exception(405, "更新失败, 请重试");
        }
        return array();
    }
}