<?php

class Service_Data_Group {

    private $daoGroup ;

    const STATUS_RUN = 1; // 开班
    const STATUS_DONE = 2; // 结束

    public function __construct() {
        $this->daoGroup = new Dao_Group () ;
    }

    public function getGroupById ($id){
        $arrConds = array(
            'id'  => $id,
        );

        $arrFields = $this->daoGroup->arrFieldsMap;

        $Group = $this->daoGroup->getRecordByConds($arrConds, $arrFields);
        if (empty($Group)) {
            return array();
        }

        return $Group;
    }


    public function editGroupStuPrice ($id, $studentPrice) {
        $arrConds = array(
            'id'  => $id,
        );
        $profile = array(
            "student_price" => json_encode($studentPrice),
        );

        return $this->daoGroup->updateByConds($arrConds, $profile);
    }

    public function editGroup ($id, $params) {
        $arrConds = array(
            'id'  => $id,
        );

        $now = time();
        $this->daoGroup->startTransaction();

        $profile = array(
            "name"  => $params['name'], 
            "descs"  => $params['descs'] ,
            "price" => $params['price'] ,
            "area_op" => $params['area_op'],
            "status" => $params['status'] ,
            "duration" => $params['duration'],
            "discount" => $params['discount'],
            "update_time"  => $now, 
        );


        $ret = $this->daoGroup->updateByConds($arrConds, $profile);
        if ($ret == false) {
            $this->daoGroup->rollback();
            return false;
        }

        $daoGroupMap = new Dao_Groupmap();
        if (!empty($params['diff2_student'])) {
            foreach ($params['diff2_student'] as $studentId) {
            
                $profile = array( 
                    "student_id" => intval($studentId),
                    "group_id" => $id,
                    "create_time" => $now,
                    "update_time" => $now,
                );
                $ret = $daoGroupMap->insertRecords($profile);
                if ($ret == false) {
                    $this->daoGroup->rollback();
                    return false;
                }
            }
        }
        if (!empty($params['diff1_student'])) {
            foreach ($params['diff1_student'] as $studentId) {
                $conds = array(
                    'student_id' => intval($studentId),
                    "group_id" => $id,
                );
                $ret = $daoGroupMap->deleteByConds($conds);
                if ($ret == false) {
                    $this->daoGroup->rollback();
                    return false;
                }
            }
        }
        
        $this->daoGroup->commit();
        return true;        
    }

    public function createGroup ($params) {
        $now = time();
        $this->daoGroup->startTransaction();

        $profile = array(
            "name"  => $params['name'], 
            "descs"  => $params['descs'] ,
            "price" => $params['price'] ,
            "area_op" => $params['area_op'],
            "status" => $params['status'] ,
            "duration" => $params['duration'],
            "discount" => $params['discount'],
            "create_time"  => $now, 
            "update_time"  => $now, 
        );

        $ret = $this->daoGroup->insertRecords($profile);
        if ($ret == false) {
            $this->daoGroup->rollback();
            return false;
        }

        $groupId = $this->daoGroup->getInsertId();
        if (empty($groupId)) {
            $this->daoGroup->rollback();
            return false;
        }

        if (!empty($params['student_ids'])) {
            $daoGroupMap = new Dao_Groupmap();
            foreach ($params['student_ids'] as $studentId) {
                
                $profile = array( 
                    "group_id" => $groupId,
                    "student_id" => intval($studentId),
                    "update_time"  => $now, 
                    "create_time" => $now,
                );
                $ret = $daoGroupMap->insertRecords($profile);
                if ($ret == false) {
                    $this->daoGroup->rollback();
                    return false;
                }
            }
        }
        
        $this->daoGroup->commit();
        return true;
    }


    public function deleteGroup ($id) {
        $this->daoGroup->startTransaction();
        
        $dao = new Dao_Schedule ();
        $conds = array(
            'group_id' => $id,
            'state' => 1,
        );
        $ret = $dao->deleteByConds($conds);
        if ($ret === false) {
            $this->daoGroup->rollback();
            return false;
        }

        $conds = array(
            'id' => $id,
        );
        $ret = $this->daoGroup->deleteByConds($conds);
        if ($ret === false) {
            $this->daoGroup->rollback();
            return false;
        }

        $daoGroupMap = new Dao_Groupmap ();
        $conds = array(
            'group_id' => $id,
        );
        $ret = $daoGroupMap->deleteByConds($conds);
        if ($ret === false) {
            $this->daoGroup->rollback();
            return false;
        }

        $this->daoGroup->commit();
        return $ret;
    }

    public function getListByConds($conds, $field = array(), $indexs = null, $appends = null) {
        $field = empty($field) || !is_array($field) ? $this->daoGroup->arrFieldsMap : $field;
        $lists = $this->daoGroup->getListByConds($conds, $field, $indexs, $appends);
        if (empty($lists)) {
            return array();
        }
        foreach ($lists as $index => $item) {
            $item['create_time']  = date('Y年m月d日', $item['create_time']);
            $item['update_time']  = date('Y年m月d日', $item['update_time']);
            $item['priceInfo'] = ($item['price'] / 100) . "元";
            $item['priceInfo2'] = ($item['price'] / 100) ;
            $item['durationInfo'] = $item['duration'] . "课时";
            $item['statusInfo'] = $item['status'] == 1 ? "online" : "offline";
            $lists[$index] = $item;
        }
        return $lists;
    }

    public function getTotalByConds($conds) {
        return  $this->daoGroup->getCntByConds($conds);
    }
}