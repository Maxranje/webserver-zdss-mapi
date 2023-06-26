<?php
class Updatearea {

    public function execute($params) {

        if (!method_exists($this, $params)) {
            var_dump("方法不存在" . $params);
            return ;
        }
        $this->$params();
    }

    public function addData(){
        $map = array(
            "新中关线下"    => array("area_id" => 1, "room_id" => 1),
            "左岸线上"      => array("area_id" => 3, "room_id" => 15), 
            "线下"          => array("area_id" => 4, "room_id" => 22), 
            "左岸线下"      => array("area_id" => 2, "room_id" => 8), 
            "左岸"          => array("area_id" => 2, "room_id" => 9), 
            "线上"          => array("area_id" => 3, "room_id" => 16), 
            "新中关room8"       => array("area_id" => 1, "room_id" => 2),
            "新中关"        => array("area_id" => 1, "room_id" => 3), 
            "新中关线上"    => array("area_id" => 3, "room_id" => 17), 
            "新中关room7"       => array("area_id" => 1, "room_id" => 4), 
            "新中关room9"       => array("area_id" => 1, "room_id" => 5), 
            "Y新中关线上"       => array("area_id" => 3, "room_id" => 18),
            "Y新中关"       => array("area_id" => 5, "room_id" => 29), 
            "Y新中关线下"       => array("area_id" => 5, "room_id" => 30), 
            "Y新中关 room7"     => array("area_id" => 5, "room_id" => 31), 
            "Y新中关 room10"    => array("area_id" => 5, "room_id" => 32),
            "东营"          => array("area_id" => 6, "room_id" => 36), 
            "东营线下"      => array("area_id" => 6, "room_id" => 37), 
            "Y线下"         => array("area_id" => 4, "room_id" => 23), 
            "北语"              => array("area_id" => 7, "room_id" => 43),
        );



        $dao = new Dao_Schedule();
        $daoGroup = new Dao_Group();
        $lists = $dao->getListByConds(array(), $dao->arrFieldsMap);

        foreach ($lists as $item) {

            $area_id = $room_id = 0;
            if (!empty($item['ext'])) {
                $json = json_decode($item['ext'], true);
                if (!empty($json['area']) && isset($map[$json['area']])) {
                    $area_id = $map[$json['area']]['area_id'];
                    $room_id = $map[$json['area']]['room_id'];
                }
            }

            if ($area_id <=0 || $room_id <=0) {
                $conds = array(
                    'id' => $item['group_id'],
                );
                $group = $daoGroup->getRecordByConds($conds, array("area"));
                if (!empty($group['area']) && isset($map[$group['area']])) {
                    $area_id = $map[$group['area']]['area_id'];
                    $room_id = $map[$group['area']]['room_id'];
                }
            }

            if ($area_id <=0 || $room_id <= 0) {
                continue;
            }

            $ret = $dao->updateByConds(array('id' => $item['id']), array('room_id' => $room_id, "area_id" => $area_id));
            if ($ret == false) {
                echo $item['id']. "失败\r\n";
            }
            echo $item['id']. "成功\r\n";
        }

        echo "done\r\n";
    }

    public function addArea () {

        $areaData = array(
            array(
                "name" => "新中关",
                "create_time" => time(),
                "update_time" => time(),
            ),
            array(
                "name" => "左岸",
                "create_time" => time(),
                "update_time" => time(),
            ),
            array(
                "name" => "线上",
                "create_time" => time(),
                "update_time" => time(),
            ),
            array(
                "name" => "线下",
                "create_time" => time(),
                "update_time" => time(),
            ),
            array(
                "name" => "Y新中关",
                "create_time" => time(),
                "update_time" => time(),
            ),
            array(
                "name" => "山东东营",
                "create_time" => time(),
                "update_time" => time(),
            ),
            array(
                "name" => "北语",
                "create_time" => time(),
                "update_time" => time(),
            ),
        );

        $daoRoom = new Dao_Room();
        $daoArea = new Dao_Area();
        foreach ($areaData as $item) {
            $ret = $daoArea->insertRecords($item);
            if ($ret) {
                $area_id = $daoArea->getInsertId();
                for($i= 1 ; $i < 8; $i++) {
                    $pp = array(
                        'area_id' => $area_id,
                        "name" => "Room" . $i,
                        "create_time" => time(),
                        "update_time" => time(),
                    );
                    $daoRoom->insertRecords($pp);
                }
            }
        }

        echo "done\n\r";
    }

}