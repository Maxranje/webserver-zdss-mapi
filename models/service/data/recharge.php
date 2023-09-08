<?php

class Service_Data_Recharge {

    const RECHARGE_TRANSFER     = 1; // 结转
    const RECHARGE_ORDERCREATE  = 2; // 订单创建

    private $daoRecharge ;

    public function __construct() {
        $this->daoRecharge = new Dao_Recharge () ;
    }

    // 获取列表
    public function getListByConds($conds, $field = array(), $indexs = null, $appends = null) {
        $field = empty($field) || !is_array($field) ? $this->daoRecharge->arrFieldsMap : $field;
        return $this->daoRecharge->getListByConds($conds, $field, $indexs, $appends);
    }

    // 获取单条
    public function getRecordByConds($conds, $field = array(), $indexs = null, $appends = null) {
        $field = empty($field) || !is_array($field) ? $this->daoRecharge->arrFieldsMap : $field;
        return $this->daoRecharge->getRecordByConds($conds, $field, $indexs, $appends);
    }

    public function getTotalByConds($conds) {
        return  $this->daoRecharge->getCntByConds($conds);
    }

}