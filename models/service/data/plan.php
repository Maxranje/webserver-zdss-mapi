<?php

class Service_Data_Plan {

    private $daoPlan ;

    public function __construct() {
        $this->daoPlan = new Dao_Plan () ;
    }

    public function getPlanById ($id) {
        $arrConds = array(
            'id'  => $id,
        );

        $data = $this->daoPlan->getRecordByConds($arrConds, $this->daoPlan->arrFieldsMap);
        if (empty($data)) {
            return array();
        }

        return $data;
    }

    public function getPlanByName ($name) {
        $arrConds = array(
            'name'  => $name,
        );

        $data = $this->daoPlan->getRecordByConds($arrConds, $this->daoPlan->arrFieldsMap);
        if (empty($data)) {
            return array();
        }

        return $data;
    }

    public function getPlanByIds ($ids) {
        $arrConds = array(
            sprintf("id in (%s)", implode(",", $ids))
        );

        $data = $this->daoPlan->getListByConds($arrConds, $this->daoPlan->arrFieldsMap);
        if (empty($data)) {
            return array();
        }

        return $data;
    }

    // 创建
    public function create ($profile) {
        return $this->daoPlan->insertRecords($profile);
    }

    // 修改
    public function update ($id, $profile) {
        return $this->daoPlan->updateByConds(array('id'=>$id), $profile);
    }

    // 删除
    public function delete ($id) {
        return $this->daoPlan->deleteByConds(array('id'=>$id));
    }

    // 列表
    public function getListByConds($conds, $field = array(), $indexs = null, $appends = null) {
        $field = empty($field) || !is_array($field) ? $this->daoPlan->arrFieldsMap : $field;
        $lists = $this->daoPlan->getListByConds($conds, $field, $indexs, $appends);
        if (empty($lists)) {
            return array();
        }
        return $lists;
    }

    // 单独一项
    public function getRecordByConds($conds, $field = array(), $indexs = null, $appends = null) {
        $field = empty($field) || !is_array($field) ? $this->daoPlan->arrFieldsMap : $field;
        $Record = $this->daoPlan->getRecordByConds($conds, $field, $indexs, $appends);
        if (empty($Record)) {
            return array();
        }
        return $Record;
    }

    public function getTotalByConds($conds) {
        return  $this->daoPlan->getCntByConds($conds);
    }
}