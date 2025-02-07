<?php

class Service_Page_Order_Change_Review extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $orderId        = empty($this->request['order_id']) ? 0 : intval($this->request['order_id']);
        $type           = empty($this->request['refund_type']) ? 1 : intval($this->request['refund_type']);
        $refundDuration = empty($this->request['refund_duration']) ? 0 : floatval($this->request['refund_duration']);
        $refundBalance  = empty($this->request['refund_balance']) ? 0 : intval(floatval($this->request['refund_balance']) * 100);
        $duration       = empty($this->request['duration']) ? 0 : floatval($this->request['duration']);
        $balance        = empty($this->request['balance']) ? 0 : intval(floatval($this->request['balance']) * 100);

        $result = array(
            "refund_review_real_balance" => "0.00",
            "refund_review_real_duration" => "0.00",
        );

        if ($orderId <= 0 || !in_array($type, [1,2])) {
            return $result;
        }

        if (($type == 1 && ($refundDuration <= 0 || $refundDuration > $duration)) || ($type == 2 && ($refundBalance <= 0 || $refundBalance > $balance))) {
            return  $result;
        }


        $serviceData = new Service_Data_Order();
        $orderInfo = $serviceData->getNmorderById($orderId);
        if (empty($orderInfo)) {
            throw new Zy_Core_Exception(405, "操作失败, 没有查到订单信息");
        }

        if ($orderInfo['balance'] <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 订单已无余额");
        }

        if ($type == 2 && $orderInfo['balance'] < $refundBalance) {
            throw new Zy_Core_Exception(405, "操作失败, 填写金额超过了订单余额");
        }

        if ($type == 1 && $orderInfo['balance'] / $orderInfo['price'] < $refundDuration) {
            throw new Zy_Core_Exception(405, "操作失败, 填写课时超过了订单剩余总课时");
        }

        $jsBalance = $jsDuration = 0;
        if ($type == 1) {
            $jsDuration = $refundDuration;
            $jsBalance = $orderInfo['price'] * $refundDuration;
        } else {
            $jsBalance = $refundBalance;
            $jsDuration = $refundBalance / $orderInfo['price'];
        }

        return array(
            "refund_review_real_balance" => sprintf("%.2f",  $jsBalance / 100),
            "refund_review_real_duration" => sprintf("%.2f",  $jsDuration),
        );
    }
}