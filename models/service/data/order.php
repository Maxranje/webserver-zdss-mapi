<?php

class Service_Data_Order {

    const DISCOUNT_Z = 1;  // 折扣
    const DISCOUNT_J = 2;  // 减免
    const DISCOUNT_TYPE = [self::DISCOUNT_Z, self::DISCOUNT_J];

    const ORDER_ABLE = 1; // 未结转或未退款
    const ORDER_DONE = 2; // 已结转或已退款

    private $daoOrder ;

    public function __construct() {
        $this->daoOrder = new Dao_Order () ;
    }

    // 根据id获取订单
    public function getOrderById ($orderId){
        $arrConds = array(
            'order_id'  => $orderId,
        );

        $Order = $this->daoOrder->getRecordByConds($arrConds, $this->daoOrder->arrFieldsMap);
        if (empty($Order)) {
            return array();
        }

        return $Order;
    }

    // 根据ids获取订单
    public function getOrderByIds ($orderIds){
        $arrConds = array(
            sprintf("order_id in (%s)", implode(",", $orderIds))
        );

        $Order = $this->daoOrder->getListByConds($arrConds, $this->daoOrder->arrFieldsMap);
        if (empty($Order)) {
            return array();
        }

        return $Order;
    }

    // 根据uids获取订单量
    public function getOrderCountByStudentUids($studentUids) {
        $conds = array(
            sprintf("student_uid in (%s)", implode(",", $studentUids))
        );
        $field = array(
            "count(order_id) as order_count", 
            "sum(balance) as balance", 
            "student_uid"
        );
        $append = array("group by student_uid");
        $lists = $this->daoOrder->getListByConds($conds, $field, null, $append);
        if (empty($lists)) {
            return array();
        }
        return $lists;
    }

    // 创建订单
    public function create ($profile) {
        $daoRecharge = new Dao_Recharge();

        $this->daoOrder->startTransaction();
        $ret = $this->daoOrder->insertRecords($profile);
        if ($ret == false) {
            $this->daoOrder->rollback();
            return false;
        }

        $orderId = $this->daoOrder->getInsertId();
        if (intval($orderId) <= 0) {
            $this->daoOrder->rollback();
            return false;
        }

        $rechargeProfile = array(
            "order_id"          => $orderId, 
            "student_uid"       => intval($profile['student_uid']), 
            "balance"           => 0, 
            "type"              => Service_Data_Recharge::RECHARGE_ORDERCREATE,
            "new_balance"       => intval($profile['balance']),
            "operator"          => OPERATOR, 
            'update_time'       => time(),
            'create_time'       => time(),
        );

        $ret = $daoRecharge->insertRecords($rechargeProfile);
        if ($ret == false) {
            $this->daoOrder->rollback();
            return false;
        }
        $this->daoOrder->commit();
        return true;
    }

    // 修改优惠价格
    public function update ($orderId, $profile) {
        return $this->daoOrder->updateByConds(array('order_id' => $orderId), $profile);
    }

    // 删除
    public function delete ($orderId) {
        return $this->daoOrder->deleteByConds(array('order_id' => $orderId));
    }

    // 获取列表
    public function getListByConds($conds, $field = array(), $indexs = null, $appends = null) {
        $field = empty($field) || !is_array($field) ? $this->daoOrder->arrFieldsMap : $field;
        $lists = $this->daoOrder->getListByConds($conds, $field, $indexs, $appends);
        if (empty($lists)) {
            return array();
        }
        return $lists;
    }

    // 获取单条
    public function getRecordByConds($conds, $field = array(), $indexs = null, $appends = null) {
        $field = empty($field) || !is_array($field) ? $this->daoOrder->arrFieldsMap : $field;
        $record = $this->daoOrder->getRecordByConds($conds, $field, $indexs, $appends);
        if (empty($record)) {
            return array();
        }
        return $record;
    }
    
    public function getTotalByConds($conds) {
        return  $this->daoOrder->getCntByConds($conds);
    }
}