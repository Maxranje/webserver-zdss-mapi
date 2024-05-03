<?php

class Service_Page_Student_Create extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin() || $this->checkPartner()) {
            throw new Zy_Core_Exception(405, "无权限操作");
        }

        $name       = empty($this->request['name']) ? "" : trim($this->request['name']);
        $phone      = empty($this->request['phone']) ? "" : trim($this->request['phone']);
        $nickname   = empty($this->request['nickname']) ? "" : trim($this->request['nickname']);
        $school     = empty($this->request['school']) ? "" : trim($this->request['school']);
        $graduate   = empty($this->request['graduate']) ? "" : trim($this->request['graduate']);
        $bpid       = empty($this->request['birthplace']) ? 0 : intval($this->request['birthplace']);
        $sex        = empty($this->request['sex']) ? "M" : trim($this->request['sex']);
        $sopuid     = empty($this->request['sopuid']) ? 0 : intval($this->request['sopuid']);

        if (empty($name) || empty($phone) || empty($nickname)) {
            throw new Zy_Core_Exception(405, "用户名或手机号等必要提交数据有空值, 请检查");
        }

        if (!is_numeric($phone) || strlen($phone) < 6 || strlen($phone) > 12) {
            throw new Zy_Core_Exception(405, "操作失败, 手机号参数错误, 6-12位数字, 请检查");
        }

        if ($bpid <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 生源地必须填写");
        }

        if ($sopuid <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 学管必须要填写");
        }

        $serviceData = new Service_Data_Profile();
        $userInfo = $serviceData->getStudentInfoByPhone($phone);
        if (!empty($userInfo)) {
            throw new Zy_Core_Exception(405, "操作失败, 手机号与已创建的学生重复, 请重新填写");
        }

        $profile = [
            "type"          => Service_Data_Profile::USER_TYPE_STUDENT , 
            "name"          => $name, 
            "nickname"      => $nickname,
            "state"         => Service_Data_Profile::STUDENT_ABLE,
            "phone"         => $phone, 
            "passport"      => $phone,
            "avatar"        => "",
            "bpid"          => $bpid,
            "school"        => $school, 
            "graduate"      => $graduate,
            "sex"           => $sex, 
            "sop_uid"       => $sopuid, 
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