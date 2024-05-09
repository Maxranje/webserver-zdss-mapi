<?php

class Service_Page_Order_Change_Refund extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $orderId        = empty($this->request['order_id']) ? 0 : intval($this->request['order_id']);
        $orderInfo      = empty($this->request['order_info']) ? "" : trim($this->request['order_info']);
        $refundType     = empty($this->request['refund_type']) ? 0 : intval($this->request['refund_type']);
        $refundBalance  = empty($this->request['refund_review_real_balance']) ? 0 : intval(floatval($this->request['refund_review_real_balance']) * 100);
        $refundDuration = empty($this->request['refund_review_real_duration']) ? 0 : floatval($this->request['refund_review_real_duration']);
        $remark         = empty($this->request['refund_remark']) ? "" : trim($this->request['refund_remark']);

        if ($orderId <= 0 || ($refundBalance <= 0 && $refundDuration <= 0)) {
            throw new Zy_Core_Exception(405, "操作失败, 订单或结转信息不能为空");
        }

        if (mb_strlen($remark) > 100) {
            throw new Zy_Core_Exception(405, "操作失败, 备注太长了");
        }

        $serviceData = new Service_Data_Order();
        $order = $serviceData->getOrderById($orderId);
        if (empty($order)) {
            throw new Zy_Core_Exception(405, "操作失败, 没有查到订单信息");
        }

        // 下线的依然可以退款
        $serviceProfile = new Service_Data_Profile();
        $studentInfo = $serviceProfile->getUserInfoByUid(intval($order['student_uid']));
        if (empty($studentInfo)) {
            throw new Zy_Core_Exception(405, "操作失败, 学员信息不存在");
        }
        
        if ($studentInfo['balance'] < 0) {
            throw new Zy_Core_Exception(405, "操作失败, 资金安全提示, 账户欠费前提下无法结转回账户");
        }

        if ($order['balance'] <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 订单已无余额");
        }

        if ($order['balance'] < $refundBalance) {
            throw new Zy_Core_Exception(405, "操作失败, 填写金额超过了订单余额");
        }

        $originDuration = floatval(sprintf("%.2f", $refundBalance / $order['price']));
        if ($originDuration != $refundDuration) {
            throw new Zy_Core_Exception(405, "操作失败, 预览课时数和实际结转课时数对不上, 请重新填写");
        }

        $serviceCurriculum = new Service_Data_Curriculum();
        $curriculum = $serviceCurriculum->getListByConds(array('order_id'=>$orderId,'state'=>Service_Data_Schedule::SCHEDULE_ABLE));
        if (!empty($curriculum)) {
            $dd = 0;
            foreach ($curriculum as $item) {
                $dd += $item['end_time'] - $item['start_time'];
            }
            $dd = $dd / 3600;

            if ($dd * $order['price'] > $order['balance'] - $refundBalance) {
                throw new Zy_Core_Exception(405, "操作失败, 订单扣除未结算课程费用后的余额 , 不足支持结转");
            }
        }

        $profile = [
            "order_id"          => $orderId, 
            "student_uid"       => intval($order['student_uid']), 
            "balance"           => $refundBalance, 
            "type"              => Service_Data_Orderchange::CHANGE_REFUND,
            "duration"          => $refundDuration,
            "order_info"        => $orderInfo,
            "operator"          => OPERATOR, 
            'update_time'       => time(),
            'create_time'       => time(),
            "ext"               => json_encode(array('remark' => $remark, "isfree" => $order['isfree'])),
        ];

        $serviceData = new Service_Data_Orderchange();
        $ret = $serviceData->create($profile, $order);
        if ($ret == false) {
            throw new Zy_Core_Exception(405, "结转到账户失败, 请重试");
        }
        return array();
    }
}