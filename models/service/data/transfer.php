<?php

class Service_Data_Transfer {

    private $daoTransfer ;

    public function __construct() {
        $this->daoTransfer = new Dao_Transfer () ;
    }

    // 结转新订单
    public function create ($profile, $orderInfo){
        $this->daoTransfer->startTransaction();
        $daoOrder = new Dao_Order();
        $ret = $daoOrder->insertRecords($profile);
        if ($ret == false) {
            $this->daoTransfer->rollback();
            return false;
        }

        // 获取新订单order_id
        $transferId = $daoOrder->getInsertId();
        $transferId = intval($transferId);
        if ($transferId <= 0) {
            $this->daoTransfer->rollback();
            return false;
        }

        $extra = json_decode($orderInfo['ext'], true);
        if (!isset($extra['transfer_balance'])) {
            $this->daoTransfer->rollback();
            return false;
        }
        $extra['transfer_balance'] = intval($extra['transfer_balance']) + intval($profile['balance']);

        // 原订单减少金额
        $p1 = array(
            sprintf("balance=balance-%d", $profile['balance']),
            "ext" => json_encode($extra),
            "update_time" => time(),
        );
        $ret = $daoOrder->updateByConds(array('order_id' => $profile['transfer_id']), $p1);
        if ($ret == false) {
            $this->daoTransfer->rollback();
            return false;
        }

        // 充值表做记录
        $ext = json_decode($profile['ext'], true);
        $rechargeProfile = array(
            "order_id"          => $transferId, 
            "student_uid"       => intval($profile['student_uid']), 
            "type"              => Service_Data_Recharge::RECHARGE_TRANSFER,
            "balance"           => intval($profile['balance']),
            "schedule_nums"     => $ext['schedule_nums'],
            "operator"          => OPERATOR, 
            'update_time'       => time(),
            'create_time'       => time(),
        );
        $daoRecharge = new Dao_Recharge();
        $ret = $daoRecharge->insertRecords($rechargeProfile);
        if ($ret == false) {
            $this->daoTransfer->rollback();
            return false;
        }

        // 结转表做记录
        $p4 = array(
            "order_id"          => $profile['transfer_id'], 
            "student_uid"       => $profile['student_uid'], 
            "transfer_id"       => $transferId,  
            "balance"           => intval($profile['balance']),
            "schedule_nums"     => $ext['schedule_nums'],
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
        return $this->daoTransfer->getListByConds($conds, $field, $indexs, $appends);
    }

    // 获取单条
    public function getRecordByConds($conds, $field = array(), $indexs = null, $appends = null) {
        $field = empty($field) || !is_array($field) ? $this->daoTransfer->arrFieldsMap : $field;
        return $this->daoTransfer->getRecordByConds($conds, $field, $indexs, $appends);
    }

    public function getTotalByConds($conds) {
        return  $this->daoTransfer->getCntByConds($conds);
    }

}