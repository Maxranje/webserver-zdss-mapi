<?php

class Service_Data_Recharge {

    const RECHARGE_NORMAL       = 1; // 普通充值或修改余额
    const RECHARGE_TRANSFER     = 2; // 结转
    const RECHARGE_ORDERCREATE  = 3; // 订单创建

    private $daoRecharge ;

    public function __construct() {
        $this->daoRecharge = new Dao_Recharge () ;
    }

    // 根据id获取订单
    public function getRechargeById ($orderId){
        $arrConds = array(
            'order_id'  => $orderId,
        );

        $Order = $this->daoRecharge->getListByConds($arrConds, $this->daoRecharge->arrFieldsMap);
        if (empty($Order)) {
            return array();
        }

        return $Order;
    }

    // 根据uid获取订单
    public function getRechargeByUid ($studentUid){
        $arrConds = array(
            'student_uid'  => $studentUid,
        );

        $Order = $this->daoRecharge->getListByConds($arrConds, $this->daoRecharge->arrFieldsMap);
        if (empty($Order)) {
            return array();
        }

        return $Order;
    }

    // 创建
    public function create ($profile) {
        $daoOrder = new Dao_Order();

        $this->daoRecharge->startTransaction();

        $orderData = array(
            sprintf("balance=balance+%d", $profile['new_balance']),
            sprintf("total_balance=total_balance+%d", $profile['new_balance']),
            "update_time" => time(),
        );
        $conds = array(
            "order_id" => $profile['order_id'],
        );
        $ret = $daoOrder->updateByConds($conds, $orderData);
        if ($ret == false) {
            $this->daoRecharge->rollback();
            return false;
        }

        $profile['new_balance'] = $profile['balance'] + $profile['new_balance'];
        $ret = $this->daoRecharge->insertRecords($profile);
        if ($ret == false) {
            $this->daoRecharge->rollback();
            return false;
        }
        $this->daoRecharge->commit();
        return true;
    }

    // 获取列表
    public function getListByConds($conds, $field = array(), $indexs = null, $appends = null) {
        $field = empty($field) || !is_array($field) ? $this->daoRecharge->arrFieldsMap : $field;
        $lists = $this->daoRecharge->getListByConds($conds, $field, $indexs, $appends);
        if (empty($lists)) {
            return array();
        }
        return $lists;
    }

    // 获取单条
    public function getRecordByConds($conds, $field = array(), $indexs = null, $appends = null) {
        $field = empty($field) || !is_array($field) ? $this->daoRecharge->arrFieldsMap : $field;
        $record = $this->daoRecharge->getRecordByConds($conds, $field, $indexs, $appends);
        if (empty($record)) {
            return array();
        }
        return $record;
    }

    public function getTotalByConds($conds) {
        return  $this->daoRecharge->getCntByConds($conds);
    }

}