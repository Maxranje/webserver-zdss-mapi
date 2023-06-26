<?php

// 创建校区
class Service_Page_Area_Create extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $areaId     = empty($this->request['area_id']) ? 0 : intval($this->request['area_id']);
        $areaName   = empty($this->request['area_name']) ? "" : trim($this->request['area_name']);
        $roomName   = empty($this->request['room_name']) ? "" : trim($this->request['room_name']);

        if ((empty($areaName) && empty($areaId)) || empty($roomName) ){
            throw new Zy_Core_Exception(405, "校区参数和房间参数不能为空");
        }

        $serviceData = new Service_Data_Area();

        if (!empty($areaName)) {
            $area = $serviceData->getAreaByName($areaName); 
            if (!empty($area)) {
                throw new Zy_Core_Exception(405, "校区名字有重复");
            }
            $ret = $serviceData->createArea($areaName, $roomName);
        } else if (!empty($areaId)){
            $room = $serviceData->getRoomByName($areaId, $roomName); 
            if (!empty($room)) {
                throw new Zy_Core_Exception(405, "同校区房间名字有重复");
            }
            $ret = $serviceData->createRoom($areaId, $roomName);
        } else {
            throw new Zy_Core_Exception(405, "请求参数错误");
        }

        if ($ret == false) {
            throw new Zy_Core_Exception(405, "创建失败, 请重试");
        }
        return array();
    }
}