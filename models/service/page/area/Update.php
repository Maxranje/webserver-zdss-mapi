<?php

class Service_Page_Area_Update extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限");
        }

        $rid        = empty($this->request['rid']) ? 0 : intval($this->request['rid']);
        $aid        = empty($this->request['aid']) ? 0 : intval($this->request['aid']);
        $areaName   = empty($this->request['area_name']) ? "" : strval($this->request['area_name']);
        $roomName   = empty($this->request['room_name']) ? "" : strval($this->request['room_name']);
        $isOnline   = empty($this->request['is_online']) || !in_array($this->request['is_online'], Service_Data_Area::STATE) ? Service_Data_Area::OFFLINE : intval($this->request['is_online']);

        if ($rid <= 0 || $aid <= 0 || empty($areaName) || empty($roomName)) {
            throw new Zy_Core_Exception(405, "操作失败, 部分参数为空, 请检查");
        }

        $serviceData = new Service_Data_Area();

        $area = $serviceData->getAreaById($aid, true);
        if (empty($area)) {
            throw new Zy_Core_Exception(405, "操作失败, 校区信息不存在");
        }

        $rooms = array_column($area['rooms'], null, 'id');
        if (empty($rooms[$rid])) {
            throw new Zy_Core_Exception(405, "操作失败, 校区没有这个教室");
        }

        if ($area['name'] != $areaName) {
            $area = $serviceData->getAreaByName($areaName); 
            if (!empty($area)) {
                throw new Zy_Core_Exception(405, "操作失败, 校区名字有重复");
            }
        }

        if ($rooms[$rid]['name'] != $roomName) {
            $room = $serviceData->getRoomByName($aid, $roomName); 
            if (!empty($room)) {
                throw new Zy_Core_Exception(405, "操作失败, 同校区房间名字有重复");
            }
        }

        $ret = $serviceData->updateArea($aid, $rid, $areaName, $roomName, $isOnline);
        if ($ret === false) {
            throw new Zy_Core_Exception(405, "更新失败, 请重试");
        }
        return array();
    }
}