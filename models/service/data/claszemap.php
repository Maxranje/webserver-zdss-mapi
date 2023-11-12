<?php

class Service_Data_Claszemap {

    private $daoClaszemap ;

    public function __construct() {
        $this->daoClaszemap = new Dao_Claszemap () ;
    }

    public function getClaszemapById ($id) {
        $arrConds = array(
            'id'  => $id,
        );

        $data = $this->daoClaszemap->getRecordByConds($arrConds, $this->daoClaszemap->arrFieldsMap);
        if (empty($data)) {
            return array();
        }

        return $data;
    }

    public function getClaszemapByName ($name) {
        $arrConds = array(
            'name'  => $name,
        );

        $data = $this->daoClaszemap->getRecordByConds($arrConds, $this->daoClaszemap->arrFieldsMap);
        if (empty($data)) {
            return array();
        }

        return $data;
    }

    public function getClaszemapByIds ($ids) {
        $arrConds = array(
            sprintf("id in (%s)", implode(",", $ids))
        );

        $data = $this->daoClaszemap->getListByConds($arrConds, $this->daoClaszemap->arrFieldsMap);
        if (empty($data)) {
            return array();
        }

        return $data;
    }

    // 创建
    public function create ($profile) {
        return $this->daoClaszemap->insertRecords($profile);
    }

    // 修改
    public function update ($id, $profile) {
        return $this->daoClaszemap->updateByConds(array('id'=>$id), $profile);
    }

    // 删除
    public function delete ($id) {
        return $this->daoClaszemap->deleteByConds(array('id'=>$id));
    }

    // 列表
    public function getListByConds($conds, $field = array(), $indexs = null, $appends = null) {
        $field = empty($field) || !is_array($field) ? $this->daoClaszemap->arrFieldsMap : $field;
        $lists = $this->daoClaszemap->getListByConds($conds, $field, $indexs, $appends);
        if (empty($lists)) {
            return array();
        }
        return $lists;
    }

    // 单独一项
    public function getRecordByConds($conds, $field = array(), $indexs = null, $appends = null) {
        $field = empty($field) || !is_array($field) ? $this->daoClaszemap->arrFieldsMap : $field;
        $Record = $this->daoClaszemap->getRecordByConds($conds, $field, $indexs, $appends);
        if (empty($Record)) {
            return array();
        }
        return $Record;
    }

    public function getTotalByConds($conds) {
        return  $this->daoClaszemap->getCntByConds($conds);
    }
}