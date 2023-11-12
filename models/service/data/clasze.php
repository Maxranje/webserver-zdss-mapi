<?php

class Service_Data_Clasze {

    private $daoClasze ;

    public function __construct() {
        $this->daoClasze = new Dao_Clasze () ;
    }

    public function getClaszeById ($id) {
        $arrConds = array(
            'id'  => $id,
        );

        $data = $this->daoClasze->getRecordByConds($arrConds, $this->daoClasze->arrFieldsMap);
        if (empty($data)) {
            return array();
        }

        return $data;
    }

    public function getClaszeByName ($name) {
        $arrConds = array(
            'name'  => $name,
        );

        $data = $this->daoClasze->getRecordByConds($arrConds, $this->daoClasze->arrFieldsMap);
        if (empty($data)) {
            return array();
        }

        return $data;
    }

    public function getClaszeByIds ($ids) {
        $arrConds = array(
            sprintf("id in (%s)", implode(",", $ids))
        );

        $data = $this->daoClasze->getListByConds($arrConds, $this->daoClasze->arrFieldsMap);
        if (empty($data)) {
            return array();
        }

        return $data;
    }

    // 创建
    public function create ($profile) {
        return $this->daoClasze->insertRecords($profile);
    }

    // 修改
    public function update ($id, $profile) {
        return $this->daoClasze->updateByConds(array('id'=>$id), $profile);
    }

    // 删除
    public function delete ($id) {
        $this->daoClasze->startTransaction();
        
        // 删掉学生关联
        $daoUser = new Dao_Claszemap();
        $ret = $daoUser->deleteByConds(array("cid" => $id));
        if ($ret == false) {
            $this->daoClasze->rollback();
            return false;
        }

        // 删掉记录
        $ret =  $this->daoClasze->deleteByConds(array('id'=>$id));
        if ($ret == false) {
            $this->daoClasze->rollback();
            return false;
        }
        $this->daoClasze->commit();
        return true;
    }

    // 列表
    public function getListByConds($conds, $field = array(), $indexs = null, $appends = null) {
        $field = empty($field) || !is_array($field) ? $this->daoClasze->arrFieldsMap : $field;
        $lists = $this->daoClasze->getListByConds($conds, $field, $indexs, $appends);
        if (empty($lists)) {
            return array();
        }
        return $lists;
    }

    // 单独一项
    public function getRecordByConds($conds, $field = array(), $indexs = null, $appends = null) {
        $field = empty($field) || !is_array($field) ? $this->daoClasze->arrFieldsMap : $field;
        $Record = $this->daoClasze->getRecordByConds($conds, $field, $indexs, $appends);
        if (empty($Record)) {
            return array();
        }
        return $Record;
    }

    public function getTotalByConds($conds) {
        return  $this->daoClasze->getCntByConds($conds);
    }
}