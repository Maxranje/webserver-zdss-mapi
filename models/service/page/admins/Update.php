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
        $type       = empty($this->request['type']) ? 0 : intval($this->request['type']);
        $bpid       = empty($this->request['birthplace']) ? 0 : intval($this->request['birthplace']);

        if ($uid <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 需要选中管理员");
        }

        if (empty($name) || empty($phone) || empty($nickname)) {
            throw new Zy_Core_Exception(405, "操作失败, 管理员名或手机号等提交数据有空值");
        }

        if (!in_array($type, [Service_Data_Profile::USER_TYPE_ADMIN, Service_Data_Profile::USER_TYPE_PARTNER])) {
            throw new Zy_Core_Exception(405, "操作失败, 管理员类型必须是系统管理员或合作方管理员");
        }

        if ($type == Service_Data_Profile::USER_TYPE_PARTNER && $bpid <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 合作方管理员必须选择一个生源地");
        }

        if ($type == Service_Data_Profile::USER_TYPE_ADMIN) {
            $bpid = 0;
        }

        $serviceData = new Service_Data_Profile();
        $userInfo = $serviceData->getUserInfoByUid($uid);
        if (empty($userInfo)) {
            throw new Zy_Core_Exception(405, "操作失败, 无法查到需要修改的管理员");
        }

        $userInfo = $serviceData->getUserInfoByNameAndPhone($name, $phone);
        if (!empty($userInfo) && $userInfo['uid'] != $uid) {
            throw new Zy_Core_Exception(405, "操作失败, 用户名/手机号关联的账户已存在");
        }

        $profile = [
            "type"          => $type, 
            "name"          => $name, 
            "nickname"      => $nickname, 
            "phone"         => $phone, 
            "state"         => Service_Data_Profile::STUDENT_ABLE,
            "avatar"        => "",
            "sex"           => "M" , 
            "bpid"          => $bpid,
            "update_time"   => time() , 
        ];

        $ret = $serviceData->editUserInfo($uid, $profile);
        if ($ret === false) {
            throw new Zy_Core_Exception(405, "更新失败, 请重试");
        }
        return array();
    }
}