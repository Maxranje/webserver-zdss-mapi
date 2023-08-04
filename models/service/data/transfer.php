<?php

class Service_Data_Transfer {

    const TRANSFER_HAS = 1;
    const TRANSFER_NEW = 2;

    private $daoTransfer ;
    private $daoRecharge;
    private $daoOrder;

    public function __construct() {
        $this->daoTransfer = new Dao_Transfer () ;
        $this->daoRecharge = new Dao_Recharge () ;
        $this->daoOrder = new Dao_Order () ;
    }


    // 创建
    public function createTransfer ($profile) {
        if ($profile['type'] == 1) {
            return $this->createTransferById ($profile);
        } else {
            return $this->createTransferByNew ($profile);
        }
    }

    // 结转已有订单
    private function createTransferById ($profile){
        if (empty($profile['order_info']) || empty($profile['transfer_info'])) {
            return false;
        }

        $this->daoTransfer->startTransaction();

        $orderInfo = $profile['order_info'];
        $transferInfo = $profile['transfer_info'];

        // 原始订单设置状态
        $conds = array(
            "order_id" => intval($orderInfo['order_id']),
        );

        $p1 = array(
            "is_transfer"   => Service_Data_Order::ORDER_DONE,
            "transfer_id"   => intval($transferInfo['order_id']),
            "balance"       => 0,
            "update_time"   => time(),
        );
        $ret = $this->daoOrder->updateByConds($conds, $p1);
        if ($ret == false) {
            $this->daoTransfer->rollback();
            return false;
        }

        // 目标订单设置余额
        $conds = array(
            "order_id" => intval($transferInfo['order_id']),
        );

        $p2 = array(
            sprintf("balance=balance+%d", intval($orderInfo['balance'])),
            sprintf("total_balance=total_balance+%d", intval($orderInfo['balance'])),
            "update_time" => time(),
        );
        $ret = $this->daoOrder->updateByConds($conds, $p2);
        if ($ret == false) {
            $this->daoTransfer->rollback();
            return false;
        }

        // 充值表做记录
        $p3 = array(
            "order_id"          => intval($orderInfo['order_id']), 
            "student_uid"       => intval($orderInfo['student_uid']), 
            "type"              => Service_Data_Recharge::RECHARGE_TRANSFER, // 结转类型充值
            "balance"           => intval($transferInfo['balance']), 
            "new_balance"       => intval($transferInfo['balance']) + intval($orderInfo['balance']),
            "operator"          => OPERATOR, 
            'update_time'       => time(),
            'create_time'       => time(),
        );

        $ret = $this->daoRecharge->insertRecords($p3);
        if ($ret == false) {
            $this->daoTransfer->rollback();
            return false;
        }

        // 结转表做记录
        $p4 = array(
            "order_id"          => intval($orderInfo['order_id']),
            "student_uid"       => intval($orderInfo['student_uid']), 
            "transfer_id"       => intval($transferInfo['order_id']),
            "type"              => self::TRANSFER_HAS, // 已有订单结转
            "balance"           => intval($orderInfo['balance']),
            "operator"          => OPERATOR, 
            'update_time'       => time(),
            'create_time'       => time(),
        );

        $ret = $this->daoTransfer->insertRecords($p4);
        if ($ret == false) {
            $this->daoTransfer->rollback();
            return false;
        }
        $this->daoTransfer->commit();
        return true;
    }

    // 结转新订单
    private function createTransferByNew ($profile){
        if (empty($profile['order_info']) || empty($profile['subject_info'])) {
            return false;
        }

        $this->daoTransfer->startTransaction();

        $orderInfo = $profile['order_info'];
        $subjectInfo = $profile['subject_info'];

        // 创建新订单
        $p1 = [
            "subject_id"        => intval($subjectInfo['id']), 
            "student_uid"       => intval($orderInfo['student_uid']), 
            "balance"           => intval($orderInfo['balance']) + intval($profile['new_balance']), 
            "total_balance"     => intval($orderInfo['balance']) + intval($profile['new_balance']), 
            "discount"          => $profile['discount'], 
            "discount_type"     => $profile['discount_type'],
            "is_transfer"       => Service_Data_Order::ORDER_ABLE,
            "is_refund"         => Service_Data_Order::ORDER_ABLE,
            'update_time'       => time(),
            'create_time'       => time(),
        ];
        $ret = $this->daoOrder->insertRecords($p1);
        if ($ret == false) {
            $this->daoTransfer->rollback();
            return false;
        }

        // 获取新订单order_id
        $transferId = $this->daoOrder->getInsertId();
        if (intval($transferId) <= 0) {
            $this->daoTransfer->rollback();
            return false;
        }

        // 原始订单设置状态
        $conds = array(
            "order_id" => intval($orderInfo['order_id']),
        );

        $p2 = array(
            "is_transfer" => Service_Data_Order::ORDER_DONE,
            "transfer_id" => intval($transferId),
            "balance"     => 0,  
            "update_time" => time(),
        );
        $ret = $this->daoOrder->updateByConds($conds, $p2);
        if ($ret == false) {
            $this->daoTransfer->rollback();
            return false;
        }

        // 充值表做记录
        $p3 = array(
            "order_id"          => intval($orderInfo['order_id']), 
            "student_uid"       => intval($orderInfo['student_uid']), 
            "type"              => Service_Data_Recharge::RECHARGE_TRANSFER,
            "balance"           => 0, 
            "new_balance"       => intval($p1['balance']),
            "operator"          => OPERATOR, 
            'update_time'       => time(),
            'create_time'       => time(),
        );

        $ret = $this->daoRecharge->insertRecords($p3);
        if ($ret == false) {
            $this->daoTransfer->rollback();
            return false;
        }

        // 结转表做记录
        $p4 = array(
            "order_id"          => intval($orderInfo['order_id']), 
            "student_uid"       => intval($orderInfo['student_uid']), 
            "transfer_id"       => intval($transferId),
            "type"              => self::TRANSFER_NEW,
            "balance"           => intval($orderInfo['balance']),
            "operator"          => OPERATOR, 
            'update_time'       => time(),
            'create_time'       => time(),
        );

        $ret = $this->daoTransfer->insertRecords($p4);
        if ($ret == false) {
            $this->daoTransfer->rollback();
            return false;
        }
        $this->daoTransfer->commit();
        return true;
    }

    // 获取列表
    public function getListByConds($conds, $field = array(), $indexs = null, $appends = null) {
        $field = empty($field) || !is_array($field) ? $this->daoTransfer->arrFieldsMap : $field;
        $lists = $this->daoTransfer->getListByConds($conds, $field, $indexs, $appends);
        if (empty($lists)) {
            return array();
        }
        return $lists;
    }

    // 获取单条
    public function getRecordByConds($conds, $field = array(), $indexs = null, $appends = null) {
        $field = empty($field) || !is_array($field) ? $this->daoTransfer->arrFieldsMap : $field;
        $record = $this->daoTransfer->getRecordByConds($conds, $field, $indexs, $appends);
        if (empty($record)) {
            return array();
        }
        return $record;
    }

    public function getTotalByConds($conds) {
        return  $this->daoTransfer->getCntByConds($conds);
    }

}