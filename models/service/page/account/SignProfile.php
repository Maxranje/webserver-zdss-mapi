<?php

class Service_Page_Account_SignProfile extends Zy_Core_Service{

    public function execute (){
        $serviceData = new Service_Data_Profile();
        $userInfo = $serviceData->getUserInfoByUid(intval($this->adption["userid"]));
        if (empty($userInfo)) {
            throw new Zy_Core_Exception(401, "无法获取用户信息");
        }

        return $this->getAuthInfo($userInfo);
    }
}