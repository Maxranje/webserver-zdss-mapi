<?php

class Service_Data_Area {

    const ONLINE    = 1;
    const OFFLINE   = 2;
    const STATE     = [self::ONLINE, self::OFFLINE];

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

        $Area = $this->daoArea->getRecordByConds($arrConds, $this->daoArea->arrFieldsMap);
        if (empty($Area)) {
            return array();
        }

        if (!$withRoom) {
            return $Area;
        }

        $Area['rooms'] = $this->getRoomListByAid($areaId);

        return $Area;
    }

    public function getAreaByIds ($areaIds, $withRoom = true) {
        $arrConds = array(
            sprintf("id in (%s)", implode(",", $areaIds))
        );

        $Areas = $this->daoArea->getListByConds($arrConds, $this->daoArea->arrFieldsMap);
        if (empty($Areas)) {
            return array();
        }

        if (!$withRoom) {
            return $Areas;
        }

        $Areas = array_column($Areas, null, "id");
        $roomLists = $this->getRoomListByAids($areaIds);
        // 把所有房间绑定到
        foreach ($roomLists as $room) {
            if (isset($Areas[$room['area_id']])) {
                $Areas[$room['area_id']]['rooms'][] = $room;
            }
        }

        return array_values($Areas);
    }

    public function getAreaRoomById ($areaId, $roomId) {
        $arrConds = array(
            'id'  => $roomId,
            'area_id' => $areaId,
        );
        return $this->daoRoom->getRecordByConds($arrConds, $this->daoRoom->arrFieldsMap);
    }

    public function getAreaByName ($areaName) {
        return $this->daoArea->getRecordByConds(array('name'  => $areaName), $this->daoArea->arrFieldsMap);
    }

    public function getRoomByName ($areaId, $roomName) {
        return $this->daoRoom->getRecordByConds(array("area_id" => $areaId,'name'  => $roomName), $this->daoRoom->arrFieldsMap);
    }

    public function getRoomListByAid ($aid) {
        return $this->daoRoom->getListByConds(array('area_id'  => $aid), $this->daoRoom->arrFieldsMap);
    }

    public function getRoomListByAids ($aids) {
        return $this->daoRoom->getListByConds(array(sprintf("area_id in (%s)", implode(",", $aids))), $this->daoRoom->arrFieldsMap);
    }


    // 获取房间列表
    public function getRoomListByConds ($arrConds) {
        return $this->daoRoom->getListByConds($arrConds, $this->daoRoom->arrFieldsMap);
    }

    // 获取校区列表
    public function getAreaListByConds ($arrConds) {
        return $this->daoArea->getListByConds($arrConds, $this->daoArea->arrFieldsMap);
    }

    // 创建area 和room
    public function createArea ($profile) {
        $this->daoArea->startTransaction();
        $areaParams = array(
            'name' => $profile['area_name'],
            "is_online" => $profile['is_online'],
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
        if (intval($aid) <= 0) {
            $this->daoArea->rollback();
            return false;
        }
        
        // 添加room
        $p1 = array(
            "area_id" => intval($aid),
            "name" => $profile['room_name'],
            'create_time' => time(),
            'update_time' => time(),
        );
        $ret = $this->createRoom($p1);
        if ($ret == false) {
            $this->daoArea->rollback();
            return false;
        }
        $this->daoArea->commit();
        return true;
    }

    // creat room
    public function createRoom ($profile) {
        return $this->daoRoom->insertRecords($profile);
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

        $daoSchedule = new Dao_Schedule();
        $ret = $daoSchedule->updateByConds(array('room_id' => $rid), array('room_id'=>0));
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

            // 删除校区
            $ret = $daoSchedule->updateByConds(array('area_id' => $aid), array('area_id'=>0));
            if ($ret == false) {
                $this->daoRoom->rollback();
                return false;
            }
        }
        $this->daoRoom->commit();
        return true;
    }

    // update
    public function updateArea ($aid, $rid, $areaName, $roomName, $isOnline) {

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
            "is_online"=>$isOnline,
        );
        $ret = $this->daoArea->updateByConds($conds, $params);
        if ($ret == false) {
            $this->daoRoom->rollback();
            return false;
        }
        $this->daoRoom->commit();
        return true;
    }
}