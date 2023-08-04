<?php

class Service_Page_Admins_Create extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkSuper()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $name       = empty($this->request['name']) ? "" : trim($this->request['name']);
        $phone      = empty($this->request['phone']) ? "" : trim($this->request['phone']);
        $nickname   = empty($this->request['nickname']) ? "" : trim($this->request['nickname']);

        if (empty($name) || empty($phone) || empty($nickname)) {
            throw new Zy_Core_Exception(405, "操作失败, 管理员名或手机号等提交数据有空值");
        }

        $serviceData = new Service_Data_Profile();
        $userInfo = $serviceData->getUserInfoByNameAndPass($name, $phone);
        if (!empty($userInfo)) {
            throw new Zy_Core_Exception(405, "操作失败, 用户名/手机号关联的账户已存在");
        }

        $profile = [
            "type"          => Service_Data_Profile::USER_TYPE_ADMIN , 
            "nickname"      => $nickname, 
            "name"          => $name, 
            "phone"         => $phone, 
            "state"         => Service_Data_Profile::STUDENT_ABLE,
            "avatar"        => "",
            "sex"           => "M" , 
            "create_time"   => time() , 
            "update_time"   => time() , 
        ];

        $ret = $serviceData->createUserInfo($profile);
        if ($ret == false) {
            throw new Zy_Core_Exception(405, "创建失败, 请重试");
        }
        return array();
    }
}