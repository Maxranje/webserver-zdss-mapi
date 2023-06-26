<?php
class Updatecapital {

    public function execute($params) {

        if (!method_exists($this, $params)) {
            var_dump("方法不存在" . $params);
            return ;
        }
        $this->$params();
    }

    public function capital () {
        $dao = new Dao_Capital();

        $data = $dao->getListByConds(array(), $dao->arrFieldsMap);
        foreach ($data as $item) {
            $json = json_decode($item['ext'], true);
            $profile = array(
                "column_id" => $json['column']['id'],
                "group_id" => $json['group']['id'],
                "schedule_id" => $json['job']['id'],
            );
            $conds = array(
                'id' => $item['id'],
            );
            $ret = $dao->updateByConds($conds, $profile);
            if ($ret) {
                echo $item['id'] . " 执行成功 \r\n";
            } else {
                echo $item['id'] . " 执行失败 \r\n";
            }
        }
    }

    public function singleprice () {
        $daoUser = new Dao_User();
        $daoGroup = new Dao_Group();
        $daoGroupMap = new Dao_Groupmap();
        $data = $daoUser->getListByConds(array(), $daoUser->arrFieldsMap);
        foreach ($data as $item) {
            if (isset($item['student_price'])) {
                $uid = intval($item['uid']);
                
                $lists = $daoGroupMap->getListByConds(array('student_id' => $uid), array("group_id"));
                if (!empty($lists)) {
                    foreach ($lists as $groupmap) {
                        $gid = intval($groupmap['group_id']);
                        $g = $daoGroup->getRecordByConds(array('id' => $gid), array("student_price"));
                        if (!empty($g)) {
                            $p = empty($g['student_price']) ? array() : json_decode($g['student_price'], true);
                            $p[$uid] = $item['student_price'];
                            $p = json_encode($p);
                            $ret = $daoGroup->updateByConds(array("id" => $gid), array("student_price" => $p));
                            if ($ret == false) {
                                echo $uid . " - " . $gid .  " 执行失败 \r\n";
                            } else {
                                echo $uid . " - " . $gid .  " 执行成功 \r\n";
                            }
                        }
                    }
                }
            }
        }
    }

}