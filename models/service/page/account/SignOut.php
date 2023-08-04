<?php

class Service_Page_Account_SignOut extends Zy_Core_Service{

    public function execute () {
        $serviceData = new Service_Data_Profile();
        $ret = $serviceData->delUserSession();
        if ($ret == false) {
            throw new Zy_Core_Exception(405, "系统错误请重试");
        }
        return true;
    }
}