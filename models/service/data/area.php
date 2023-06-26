<?php

class Service_Data_Area {

    private $daoArea ;
    private $daoRoom ;

    public function __construct() {
        $this->daoArea = new Dao_Area () ;
        $this->daoRoom = new Dao_Room () ;
    }

    public function getAreaById ($areaId, $withRoom = true) {
        $arrConds = array(
            'id'  => $areaId,
        );

        $arrFields = $this->daoArea->arrFieldsMap;

        $Area = $this->daoArea->getRecordByConds($arrConds, $arrFields);
        if (empty($Area)) {
            return array();
        }

        if (!$withRoom) {
            return $Area;
        }

        $Area['rooms'] = $this->getRoomListByAid($areaId);

        return $Area;
    }

    public function getAreaRoomById ($areaId, $roomId) {
        $arrConds = array(
            'id'  => $roomId,
            'area_id' => $areaId,
        );

        $arrFields = $this->daoArea->arrFieldsMap;

        $Room = $this->daoRoom->getRecordByConds($arrConds, $arrFields);
        if (empty($Room)) {
            return array();
        }

        return $Room;
    }

    public function getAreaByName ($areaName) {
        $arrConds = array(
            'name'  => $areaName,
        );

        $arrFields = $this->daoArea->arrFieldsMap;

        $Area = $this->daoArea->getRecordByConds($arrConds, $arrFields);
        if (empty($Area)) {
            return array();
        }
        return $Area;
    }

    public function getRoomByName ($areaId, $roomName) {
        $arrConds = array(
            "area_id" => $areaId,
            'name'  => $roomName,
        );

        $arrFields = $this->daoArea->arrFieldsMap;

        $Room = $this->daoRoom->getRecordByConds($arrConds, $arrFields);
        if (empty($Room)) {
            return array();
        }
        return $Room;
    }

    public function getRoomListByAid ($aid) {
        $arrConds = array(
            'area_id'  => $aid,
        );

        $arrFields = $this->daoRoom->arrFieldsMap;

        $rooms = $this->daoRoom->getListByConds($arrConds, $arrFields);
        if (empty($rooms)) {
            return array();
        }
        return $rooms;
    }

    public function getRoomListByConds ($arrConds) {
        $arrFields = $this->daoRoom->arrFieldsMap;

        $rooms = $this->daoRoom->getListByConds($arrConds, $arrFields);
        if (empty($rooms)) {
            return array();
        }
        return $rooms;
    }

    public function getAreaListByConds ($arrConds) {
        $arrFields = $this->daoArea->arrFieldsMap;

        $areas = $this->daoArea->getListByConds($arrConds, $arrFields);
        if (empty($areas)) {
            return array();
        }
        return $areas;
    }

    // 创建area 和room
    public function createArea ($areaName, $roomName) {

        $this->daoArea->startTransaction();
        $areaParams = array(
            'name' => $areaName,
            'create_time' => time(),
            'update_time' => time(),
        );
        $ret = $this->daoArea->insertRecords($areaParams);
        if ($ret == false) {
            $this->daoArea->rollback();
            return false;
        }

        // 获取插入id
        $aid = $this->daoArea->getInsertId();
        $ret = $this->createRoom($aid, $roomName);
        if ($ret == false) {
            $this->daoArea->rollback();
            return false;
        }
        $this->daoArea->commit();
        return true;
    }

    // creat room
    public function createRoom ($areaId, $roomName) {
        $roomParams = array(
            'name' => $roomName,
            'area_id' => intval($areaId),
            'create_time' => time(),
            'update_time' => time(),
        );
        return $this->daoRoom->insertRecords($roomParams);
    }

    // delete room
    public function deleteRoom ($aid, $rid, $bothDel = false) {

        $this->daoRoom->startTransaction();
        $conds = array(
            'id' => $rid,
        ) ;
        $ret = $this->daoRoom->deleteByConds($conds);
        if ($ret == false) {
            $this->daoRoom->rollback();
            return false;
        }

        if ($bothDel) {
            $conds = array(
                'id' => $aid,
            );
            $ret = $this->daoArea->deleteByConds($conds);
            if ($ret == false) {
                $this->daoRoom->rollback();
                return false;
            }
        }
        $this->daoRoom->commit();
        return true;
    }

    // update
    public function updateArea ($aid, $rid, $areaName, $roomName) {

        $this->daoRoom->startTransaction();
        $conds = array(
            'id' => $rid,
        ) ;
        $params = array(
            'name' => $roomName,
        );
        $ret = $this->daoRoom->updateByConds($conds, $params);
        if ($ret == false) {
            $this->daoRoom->rollback();
            return false;
        }

        $conds = array(
            'id' => $aid,
        );
        $params = array(
            'name' => $areaName,
        );
        $ret = $this->daoArea->updateByConds($conds, $params);
        if ($ret == false) {
            $this->daoRoom->rollback();
            return false;
        }
        $this->daoRoom->commit();
        return true;
    }

    public function getList () {
        $arrFields = $this->daoArea->arrFieldsMap;
        $AreaLists = $this->daoArea->getListByConds(array("id > 0"), $arrFields);
        if (empty($AreaLists)) {
            return array();
        }

        $arrFields = $this->daoRoom->arrFieldsMap;
        $RoomLists = $this->daoRoom->getListByConds(array("id > 0"), $arrFields);
        if (empty($RoomLists)) {
            return array();
        }

        $AreaLists = array_column($AreaLists, null, "id");

        foreach ($RoomLists as $room) {
            if (isset($AreaLists[$room['area_id']])) {
                $AreaLists[$room['area_id']]['rooms'][] = $room;
            }
        }

        return array_values($AreaLists);
    }
}