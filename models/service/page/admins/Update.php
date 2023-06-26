<?php

class Service_Page_Admins_Update extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkSuper()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $uid        = empty($this->request['uid']) ? 0 : intval($this->request['uid']);
        $name       = empty($this->request['name']) ? "" : trim($this->request['name']);
        $phone      = empty($this->request['phone']) ? "" : trim($this->request['phone']);
        $nickname   = empty($this->request['nickname']) ? "" : trim($this->request['nickname']);

        if ($uid <= 0) {
            throw new Zy_Core_Exception(405, "需要选中管理员");
        }

        if (empty($name) || empty($phone) || empty($nickname)) {
            throw new Zy_Core_Exception(405, "管理员名或手机号等提交数据有空值, 请检查");
        }

        $serviceData = new Service_Data_User_Profile();
        $userInfo = $serviceData->getUserInfoByUid($uid);
        if (empty($userInfo)) {
            throw new Zy_Core_Exception(405, "无法查到需要修改的管理员, 请确认~");
        }

        $userInfo = $serviceData->getUserInfo($name, $phone);
        if (!empty($userInfo)) {
            throw new Zy_Core_Exception(405, "用户名/手机号关联的账户已存在");
        }

        $profile = [
            "type"          => Service_Data_User_Profile::USER_TYPE_ADMIN, 
            "name"          => $name, 
            "nickname"      => $nickname, 
            "phone"         => $phone, 
            "avatar"        => "",
            "sex"           => "M" , 
            "update_time"   => time() , 
        ];

        $ret = $serviceData->editUserInfo($uid, $profile);
        if ($ret === false) {
            throw new Zy_Core_Exception(405, "更新失败, 请重试");
        }
        return array();
    }
}