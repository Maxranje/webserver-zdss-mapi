<?php

class Service_Page_Account_SignIn extends Zy_Core_Service{

    public function execute (){

        $username = empty($this->request['username']) ? "" : trim($this->request['username']);
        $passport = empty($this->request['password']) ? "" : trim($this->request['password']);

        if (empty($username) || empty($passport)) {
            throw new Zy_Core_Exception(405, "用户名或密码为空");
        }

        if (!Zy_Helper_Utils::checkStrictStr($username, 1) || !Zy_Helper_Utils::checkStr($passport)) {
            throw new Zy_Core_Exception(405, "操作失败, 内容必须是英文,数字或逗号, 6-20位之间");
        }        

        $serviceData = new Service_Data_Profile();
        $userInfo = $serviceData->getUserInfoByNameAndPass($username, $passport);
        if (empty($userInfo)) {
            throw new Zy_Core_Exception(405, "用户名或密码错误");
        }

        // 获取权限
        $serviceRoles = new Service_Data_Roles();
        list($userInfo['pages'], $userInfo['modes']) = $serviceRoles->getPageIdsByUid($userInfo['uid'], $userInfo['type']);
        $ret = $serviceData->setUserSession($userInfo);
        if (!$ret) {
            throw new Zy_Core_Exception(405, "系统错误请重试");
        }

        $ret = $this->getAuthInfo($userInfo);
        if ($ret["user"]["roleType"] == 1) {
            $ret["redirect"] = "/platform";
        } else if ($ret["user"]["type"] == Service_Data_Profile::USER_TYPE_TEACHER && $ret["user"]["roleType"] == 0) {
            $ret["redirect"] = "/details";
        } else if ($ret["user"]["type"] == Service_Data_Profile::USER_TYPE_STUDENT) {
            $ret["redirect"] = "/profile";
        }
        return $ret;
    }
}