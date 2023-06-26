<?php

class Service_Page_Roles_Lists extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkSuper()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $serviceData = new Service_Data_Roles();
        $lists = $serviceData->getListByConds(array());

        return array(
            'rows' => $lists,
            'total' => count($lists),
        );
    }
}