<?php

// 创建校区
class Service_Page_Area_Room_Create extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $areaId     = empty($this->request['area_id']) ? 0 : intval($this->request['area_id']);
        $roomName   = empty($this->request['room_name']) ? "" : trim($this->request['room_name']);

        if ($areaId <= 0 || empty($roomName) ){
            throw new Zy_Core_Exception(405, "操作失败, 校区参数和房间参数不能为空");
        }

        $serviceData = new Service_Data_Area();
        $room = $serviceData->getRoomByName($areaId, $roomName); 
        if (!empty($room)) {
            throw new Zy_Core_Exception(405, "操作失败, 同校区房间名字有重复");
        }
        $profile = array(
            'area_id' => $areaId,
            "name" => $roomName,
            'create_time' => time(),
            'update_time' => time(),
        );
        $ret = $serviceData->createRoom($profile);

        if ($ret == false) {
            throw new Zy_Core_Exception(405, "创建失败, 请重试");
        }
        return array();
    }
}