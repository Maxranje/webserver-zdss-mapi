<?php

class Service_Data_Records {

    const RECORDS_NOMARL = 1;
    const RECORDS_REVOKE = 2;

    private $daoRecords;

    public function __construct() {
        $this->daoRecords = new Dao_Records () ;
    }

    // 获取order 所有消耗的
    public function getOrderPayMoney ($orderIds) {
        $conds = array(
            sprintf("order_id in (%s)", implode(",", $orderIds)),
        );
        $field = array(
            "sum(money) as money",
            "order_id",
        );
        $appends = array(
            "group by order_id"
        );
        $lists = $this->daoRecords->getListByConds($conds, $field, null, $appends);
        if (empty($lists)) {
            return array();
        }
        return $lists;
    }

    // 获取列表
    public function getListByConds($conds, $field = array(), $indexs = null, $appends = null) {
        $field = empty($field) || !is_array($field) ? $this->daoRecords->arrFieldsMap : $field;
        $lists = $this->daoRecords->getListByConds($conds, $field, $indexs, $appends);
        if (empty($lists)) {
            return array();
        }
        return $lists;
    }

    // 获取数量
    public function getTotalByConds($conds) {
        return  $this->daoRecords->getCntByConds($conds);
    }
}