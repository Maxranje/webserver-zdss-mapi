<?php

class Service_Page_Teacher_Update extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $uid = empty($this->request['uid']) ?0 : intval($this->request['uid']);
        $name       = empty($this->request['name']) ? "" : trim($this->request['name']);
        $phone      = empty($this->request['phone']) ? "" : trim($this->request['phone']);
        $nickname   = empty($this->request['nickname']) ? "" : trim($this->request['nickname']);
        $sex        = empty($this->request['sex']) ? "M" : trim($this->request['sex']);
        $state      = empty($this->request['state']) ? 0 : intval($this->request['state']);
        $capital    = empty($this->request['capital']) ? 0 : $this->request['capital'];

        if (empty($name) || empty($nickname) || empty($phone) || !in_array($sex, ['M', "F"]) || !in_array($state, [0,1])) {
            throw new Zy_Core_Exception(405, "部分参数为空或非法, 请检查");
        }

        $serviceData = new Service_Data_User_Profile();
        $userInfo = $serviceData->getUserInfoByUid($uid);
        if (empty($userInfo)) {
            throw new Zy_Core_Exception(405, "无法查到相关用户");
        }

        $profile = [
            "type"          => Service_Data_User_Profile::USER_TYPE_TEACHER, 
            "name"          => $name , 
            "nickname"      => $nickname , 
            "phone"         => $phone, 
            "avatar"        => "",
            "sex"           => $sex, 
            "state"         => $state,
            "update_time"  => time() , 
        ];

        $needTeacherCapital = false;
        if ($capital > 0) {
            $needTeacherCapital = true;
            $userInfo['teacher_capital'] += intval($capital * 100);
            $profile['teacher_capital'] = $userInfo['teacher_capital'];
            $profile['capital'] = intval($capital * 100);
        }

        $ret = $serviceData->editUserInfo($uid, $profile, false, $needTeacherCapital);
        if ($ret === false) {
            throw new Zy_Core_Exception(405, "更新失败, 请重试");
        }
        return array();
    }
}