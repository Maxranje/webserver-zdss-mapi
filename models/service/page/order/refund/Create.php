<?php

class Service_Page_Order_Refund_Create extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $orderId    = empty($this->request['order_id']) ? 0 : intval($this->request['order_id']);

        if ($orderId <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 订单不能为空");
        }

        $serviceData = new Service_Data_Order();
        $orderInfo = $serviceData->getOrderById($orderId);
        if (empty($orderInfo)) {
            throw new Zy_Core_Exception(405, "操作失败, 没有查到订单信息");
        }

        if ($orderInfo['is_refund'] == Service_Data_Order::ORDER_DONE || $orderInfo['is_transfer'] == Service_Data_Order::ORDER_DONE) {
            throw new Zy_Core_Exception(405, "操作失败, 已结转或已退款的订单无法退款");
        }

        $serviceCurriculum = new Service_Data_Curriculum();
        $curriculum = $serviceCurriculum->getListByConds(array('order_id'=>$orderId,'state'=>Service_Data_Schedule::SCHEDULE_ABLE));
        if (!empty($curriculum)) {
            throw new Zy_Core_Exception(405, "操作失败, 当前订单还有未结算的排课, 无法退款");
        }

        // 下线的依然可以退款
        $serviceProfile = new Service_Data_Profile();
        $studentInfo = $serviceProfile->getUserInfoByUid(intval($orderInfo['student_uid']));
        if (empty($studentInfo)) {
            throw new Zy_Core_Exception(405, "操作失败, 学员信息不存在");
        }

        $profile = [
            "order_id"          => $orderId, 
            "student_uid"       => intval($orderInfo['student_uid']), 
            "balance"           => intval($orderInfo['balance']), 
            "operator"          => OPERATOR, 
            'update_time'       => time(),
            'create_time'       => time(),
        ];

        $serviceData = new Service_Data_Refund();
        $ret = $serviceData->create($profile);
        if ($ret == false) {
            throw new Zy_Core_Exception(405, "退款失败, 请重试");
        }
        return array();
    }
}