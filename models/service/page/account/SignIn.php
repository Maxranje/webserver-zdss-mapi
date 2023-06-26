<?php

class Service_Page_Account_SignIn extends Zy_Core_Service{

    public function execute (){

        $username = empty($this->request['username']) ? "" : trim($this->request['username']);
        $passport = empty($this->request['passport']) ? "" : trim($this->request['passport']);

        if (empty($username) || empty($passport)) {
            throw new Zy_Core_Exception(405, "用户名或密码为空");
        }

        $serviceData = new Service_Data_User_Profile();
        $userInfo = $serviceData->getUserInfo($username, $passport);
        if (empty($userInfo)) {
            throw new Zy_Core_Exception(405, "用户名或密码错误");
        }

        $serviceRoles = new Service_Data_Roles();
        $roles = $serviceRoles->getPagesByUid($userInfo['uid'], $userInfo['type']);

        // 设置可查看权限页面的IDS
        $userInfo['pages'] = !empty($roles) && is_array($roles) ? $roles : array();

        $data = $serviceData->setUserSession($userInfo);
        if (empty($data)) {
            throw new Zy_Core_Exception(405, "系统错误请重试");
        }

        return $data;
    }
}