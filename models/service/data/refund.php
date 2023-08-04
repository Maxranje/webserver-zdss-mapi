<?php

class Service_Data_Refund {

    private $daoRefund ;
    private $daoOrder;

    public function __construct() {
        $this->daoRefund = new Dao_Refund () ;
        $this->daoOrder = new Dao_Order();
    }

    // 创建
    public function create ($profile) {
        $this->daoRefund->startTransaction();

        $ret = $this->daoRefund->insertRecords($profile);
        if ($ret == false) {
            $this->daoRefund->rollback();
            return false;
        }

        $refundId = $this->daoRefund->getInsertId();
        if (intval($refundId) <= 0) {
            $this->daoRefund->rollback();
            return false;
        }

        $orderData = array(
            "is_refund" => Service_Data_Order::ORDER_DONE,
            "refund_id" => intval($refundId),
            "balance"   => 0,
            "update_time" => time(),
        );
        $conds = array(
            "order_id" => intval($profile['order_id']),
        );
        $ret = $this->daoOrder->updateByConds($conds, $orderData);
        if ($ret == false) {
            $this->daoRefund->rollback();
            return false;
        }
        $this->daoRefund->commit();
        return true;
    }

    // 获取列表
    public function getListByConds($conds, $field = array(), $indexs = null, $appends = null) {
        $field = empty($field) || !is_array($field) ? $this->daoRefund->arrFieldsMap : $field;
        $lists = $this->daoRefund->getListByConds($conds, $field, $indexs, $appends);
        if (empty($lists)) {
            return array();
        }
        return $lists;
    }

    // 获取单条
    public function getRecordByConds($conds, $field = array(), $indexs = null, $appends = null) {
        $field = empty($field) || !is_array($field) ? $this->daoRefund->arrFieldsMap : $field;
        $record = $this->daoRefund->getRecordByConds($conds, $field, $indexs, $appends);
        if (empty($record)) {
            return array();
        }
        return $record;
    }

    public function getTotalByConds($conds) {
        return  $this->daoRefund->getCntByConds($conds);
    }

}