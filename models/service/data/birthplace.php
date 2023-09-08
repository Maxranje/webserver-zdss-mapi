<?php

class Service_Data_Birthplace {

    private $daoBirthplace ;

    public function __construct() {
        $this->daoBirthplace = new Dao_Birthplace () ;
    }

    public function getBirthplaceById ($id) {
        $arrConds = array(
            'id'  => $id,
        );

        $data = $this->daoBirthplace->getRecordByConds($arrConds, $this->daoBirthplace->arrFieldsMap);
        if (empty($data)) {
            return array();
        }

        return $data;
    }

    public function getBirthplaceByName ($name) {
        $arrConds = array(
            'name'  => $name,
        );

        $data = $this->daoBirthplace->getRecordByConds($arrConds, $this->daoBirthplace->arrFieldsMap);
        if (empty($data)) {
            return array();
        }

        return $data;
    }

    public function getBirthplaceByIds ($ids) {
        $arrConds = array(
            sprintf("id in (%s)", implode(",", $ids))
        );

        $data = $this->daoBirthplace->getListByConds($arrConds, $this->daoBirthplace->arrFieldsMap);
        if (empty($data)) {
            return array();
        }

        return $data;
    }

    // 创建
    public function create ($profile) {
        return $this->daoBirthplace->insertRecords($profile);
    }

    // 修改
    public function update ($id, $profile) {
        return $this->daoBirthplace->updateByConds(array('id'=>$id), $profile);
    }

    // 删除
    public function delete ($id) {
        $this->daoBirthplace->startTransaction();
        
        // 删掉学生关联
        $daoUser = new Dao_User();
        $ret = $daoUser->updateByConds(array('bpid' => $id), array("bpid" => 0));
        if ($ret == false) {
            $this->daoBirthplace->rollback();
            return false;
        }

        // 删掉记录
        $ret =  $this->daoBirthplace->deleteByConds(array('id'=>$id));
        if ($ret == false) {
            $this->daoBirthplace->rollback();
            return false;
        }
        $this->daoBirthplace->commit();
        return true;
    }

    // 列表
    public function getListByConds($conds, $field = array(), $indexs = null, $appends = null) {
        $field = empty($field) || !is_array($field) ? $this->daoBirthplace->arrFieldsMap : $field;
        $lists = $this->daoBirthplace->getListByConds($conds, $field, $indexs, $appends);
        if (empty($lists)) {
            return array();
        }
        return $lists;
    }

    // 单独一项
    public function getRecordByConds($conds, $field = array(), $indexs = null, $appends = null) {
        $field = empty($field) || !is_array($field) ? $this->daoBirthplace->arrFieldsMap : $field;
        $Record = $this->daoBirthplace->getRecordByConds($conds, $field, $indexs, $appends);
        if (empty($Record)) {
            return array();
        }
        return $Record;
    }

    public function getTotalByConds($conds) {
        return  $this->daoBirthplace->getCntByConds($conds);
    }
}