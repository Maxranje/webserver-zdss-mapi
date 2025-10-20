<?php

class Service_Page_Account_SignProfile extends Zy_Core_Service{

    public function execute (){
        $serviceData = new Service_Data_Profile();
        $userInfo = $serviceData->getUserInfoByUid(intval($this->adption["userid"]));
        if (empty($userInfo)) {
            throw new Zy_Core_Exception(401, "无法获取用户信息");
        }

        if (!empty($userInfo["sop_uid"])) {
            $sop = $serviceData->getUserInfoByUid(intval($userInfo["sop_uid"]));
            $userInfo["sopname"] = empty($sop["nickname"]) ? "" : $sop["nickname"];
        }
        return $this->getAuthInfo($userInfo);
    }
}