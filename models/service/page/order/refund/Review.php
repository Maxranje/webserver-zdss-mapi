<?php

class Service_Page_Order_Refund_Review extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $orderId        = empty($this->request['order_id']) ? 0 : intval($this->request['order_id']);
        $balance        = empty($this->request['balance']) ? 0 : intval(floatval($this->request['balance']) * 100);

        if ($orderId <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 请求参数有误");
        }

        if ($balance <=0 ) {
            throw new Zy_Core_Exception(405, "操作失败, 退款金额不能为0");
        }

        $serviceData = new Service_Data_Order();
        $orderInfo = $serviceData->getOrderById($orderId);
        if (empty($orderInfo)) {
            throw new Zy_Core_Exception(405, "操作失败, 没有查到订单信息");
        }

        if ($orderInfo['balance'] <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 订单已无余额, 无法退款");
        }

        if ($orderInfo['balance'] < $balance) {
            throw new Zy_Core_Exception(405, "操作失败, 退款填写金额超过了订单余额");
        }


        return array(
            "refund_real_balance" => sprintf("%.2f", $orderInfo['balance'] / 100),
            "refund_real_price" => sprintf("%.2f", $orderInfo['price'] / 100),
            "refund_origin_schedule_nums" => sprintf("%.2f", ($orderInfo['balance'] / $orderInfo['price'])),
            "refund_schedule_nums" => sprintf("%.2f",  ($balance / $orderInfo['price'])),
        );
    }
}