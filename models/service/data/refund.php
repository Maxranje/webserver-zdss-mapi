<?php

class Service_Data_Refund {

    private $daoRefund ;

    public function __construct() {
        $this->daoRefund = new Dao_Refund () ;
    }

    // 创建
    public function create ($profile, $orderInfo) {
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

        // 退款金额更新
        $extra = json_decode($orderInfo['ext'], true);
        if (!isset($extra['refund_balance'])) {
            $this->daoRefund->rollback();
            return false;
        }
        $extra['refund_balance'] = intval($extra['refund_balance']) + intval($profile['balance']);

        $orderData = array(
            sprintf("balance=balance-%d", $profile['balance']),
            'ext' => json_encode($extra),
            'update_time' => time(),
        );
        $conds = array(
            "order_id" => intval($profile['order_id']),
        );
        $daoOrder = new Dao_Order();
        $ret = $daoOrder->updateByConds($conds, $orderData);
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
        return $this->daoRefund->getListByConds($conds, $field, $indexs, $appends);
    }

    // 获取单条
    public function getRecordByConds($conds, $field = array(), $indexs = null, $appends = null) {
        $field = empty($field) || !is_array($field) ? $this->daoRefund->arrFieldsMap : $field;
        return $this->daoRefund->getRecordByConds($conds, $field, $indexs, $appends);
    }

    public function getTotalByConds($conds) {
        return  $this->daoRefund->getCntByConds($conds);
    }

}