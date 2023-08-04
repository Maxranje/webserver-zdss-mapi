<?php

class Service_Page_Teacher_Create extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $name       = empty($this->request['name']) ? "" : trim($this->request['name']);
        $phone      = empty($this->request['phone']) ? "" : trim($this->request['phone']);
        $nickname   = empty($this->request['nickname']) ? "" : trim($this->request['nickname']);
        $sex        = empty($this->request['sex']) ? "M" : trim($this->request['sex']);

        if (empty($name) || empty($nickname) || empty($phone) || !in_array($sex, ['M', "F"])) {
            throw new Zy_Core_Exception(405, "部分参数为空或非法, 请检查");
        }

        $serviceData = new Service_Data_Profile();
        $userInfo = $serviceData->getUserInfoByNameAndPass($name, $phone);
        if (!empty($userInfo)) {
            throw new Zy_Core_Exception(405, "操作失败, 用户名/手机号绑定的用户已存在");
        }

        $profile = [
            "type"      => Service_Data_Profile::USER_TYPE_TEACHER , 
            "name"      => $name, 
            "nickname"  => $nickname, 
            "phone"     => $phone, 
            "avatar"    => "",
            "sex"       => $sex, 
            "state"     => Service_Data_Profile::STUDENT_ABLE,
            "create_time" => time(),
            "update_time" => time(),
        ];

        $ret = $serviceData->createUserInfo($profile);
        if ($ret == false) {
            throw new Zy_Core_Exception(405, "创建失败, 请重试");
        }
        return array();
    }
}