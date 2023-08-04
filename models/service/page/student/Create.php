<?php

class Service_Page_Student_Create extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $name       = empty($this->request['name']) ? "" : trim($this->request['name']);
        $phone      = empty($this->request['phone']) ? "" : trim($this->request['phone']);
        $nickname   = empty($this->request['nickname']) ? "" : trim($this->request['nickname']);
        $school     = empty($this->request['school']) ? "" : trim($this->request['school']);
        $graduate   = empty($this->request['graduate']) ? "" : trim($this->request['graduate']);
        $birthplace = empty($this->request['birthplace']) ? "" : trim($this->request['birthplace']);
        $sex        = empty($this->request['sex']) ? "M" : trim($this->request['sex']);

        if (empty($name) || empty($phone) || empty($nickname)) {
            throw new Zy_Core_Exception(405, "管理员名或手机号等提交数据有空值, 请检查");
        }

        if (!is_numeric($phone) || strlen($phone) < 6 || strlen($phone) > 12) {
            throw new Zy_Core_Exception(405, "手机号参数错误, 6-12位数字, 请检查");
        }

        $serviceData = new Service_Data_Profile();
        $userInfo = $serviceData->getUserInfoByNameAndPass($name, $phone);
        if (!empty($userInfo)) {
            throw new Zy_Core_Exception(405, "用户名/手机号绑定的用户已存在");
        }

        $profile = [
            "type"          => Service_Data_Profile::USER_TYPE_STUDENT , 
            "name"          => $name, 
            "nickname"      => $nickname,
            "state"         => Service_Data_Profile::STUDENT_ABLE,
            "phone"         => $phone, 
            "avatar"        => "",
            "birthplace"    => $birthplace,
            "school"        => $school, 
            "graduate"      => $graduate,
            "sex"           => $sex, 
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