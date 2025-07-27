<?php

class Service_Page_Api_Notice extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin() || !$this->isModeAble(Service_Data_Roles::ROLE_MODE_REVIEW_HANDLE)) {
            throw new Zy_Core_Exception(405, "无权限操作");
        }

        $serviceData = new Service_Data_Review();
        $conds = array(
            "state" => Service_Data_Review::REVIEW_ING,
        );
        $bellCount = $serviceData->getTotalByConds($conds);
        
        return array(
            "bell_notice" => $bellCount,
        );
    }
}