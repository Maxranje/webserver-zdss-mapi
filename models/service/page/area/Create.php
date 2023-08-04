<?php

// 创建校区
class Service_Page_Area_Create extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $areaName   = empty($this->request['area_name']) ? "" : trim($this->request['area_name']);
        $roomName   = empty($this->request['room_name']) ? "" : trim($this->request['room_name']);
        $isOnline   = empty($this->request['is_online']) || !in_array($this->request['is_online'], Service_Data_Area::STATE) ? Service_Data_Area::OFFLINE : intval($this->request['is_online']);

        if (empty($areaName) || empty($roomName) ){
            throw new Zy_Core_Exception(405, "操作失败, 校区参数和房间参数不能为空");
        }

        $serviceData = new Service_Data_Area();
        $area = $serviceData->getAreaByName($areaName); 
        if (!empty($area)) {
            throw new Zy_Core_Exception(405, "操作失败, 校区名字有重复");
        }
        $profile = array(
            'area_name' => $areaName,
            "room_name" => $roomName,
            "is_online" => $isOnline,
        );
        $ret = $serviceData->createArea($profile);

        if ($ret == false) {
            throw new Zy_Core_Exception(405, "创建失败, 请重试");
        }
        return array();
    }
}