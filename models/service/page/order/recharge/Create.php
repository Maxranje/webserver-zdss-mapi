<?php

class Service_Page_Order_Recharge_Create extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $newBalance = empty($this->request['new_balance']) ? 0 : intval(floatval($this->request['new_balance'] * 100));
        $orderId    = empty($this->request['order_id']) ? 0 : intval($this->request['order_id']);

        if ($orderId <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 订单ID不能为空");
        }

        $serviceData = new Service_Data_Order();
        $orderInfo = $serviceData->getOrderById($orderId);
        if (empty($orderInfo)) {
            throw new Zy_Core_Exception(405, "操作失败, 没有查到订单信息");
        }

        if ($orderInfo['is_refund'] == Service_Data_Order::ORDER_DONE || $orderInfo['is_transfer'] == Service_Data_Order::ORDER_DONE) {
            throw new Zy_Core_Exception(405, "操作失败, 已结转或已退款的订单无法充值");
        }

        $serviceProfile = new Service_Data_Profile();
        $studentInfo = $serviceProfile->getUserInfoByUid(intval($orderInfo['student_uid']));
        if (empty($studentInfo) || $studentInfo['state'] != Service_Data_Profile::STUDENT_ABLE) {
            throw new Zy_Core_Exception(405, "操作失败, 学员信息不存在或已下线");
        }

        $profile = [
            "order_id"          => $orderId, 
            "student_uid"       => intval($orderInfo['student_uid']), 
            "balance"           => intval($orderInfo['balance']), 
            "type"              => Service_Data_Recharge::RECHARGE_NORMAL,
            "new_balance"       => $newBalance,
            "operator"          => OPERATOR, 
            'update_time'       => time(),
            'create_time'       => time(),
        ];

        $serviceData = new Service_Data_Recharge();
        $ret = $serviceData->create($profile);
        if ($ret == false) {
            throw new Zy_Core_Exception(405, "创建失败, 请重试");
        }
        return array();
    }
}