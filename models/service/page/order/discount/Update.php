<?php

class Service_Page_Order_Discount_Update extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $discountType   = empty($this->request["discount_type"]) || !in_array($this->request["discount_type"], Service_Data_Order::DISCOUNT_TYPE) ? 0 : intval($this->request['discount_type']);
        $discount       = empty($this->request['discount']) ? 0 : floatval($this->request['discount']);
        $orderId        = empty($this->request['order_id']) ? 0 : intval($this->request['order_id']);

        if ($orderId <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 订单不能为空");
        }

        if ($discountType == Service_Data_Order::DISCOUNT_J && ($discount <= 0)) {
            throw new Zy_Core_Exception(405, "操作失败, 优惠配置错误, 需要配置具体价格");
        }

        if ($discountType == Service_Data_Order::DISCOUNT_Z && ($discount <= 0 || $discount >= 10)) {
            throw new Zy_Core_Exception(405, "操作失败, 折扣必须在 10 > x > 0, 小数点后一位");
        }

        if ($discountType == 0) {
            $discount = 0;
        } else if ($discountType == Service_Data_Order::DISCOUNT_Z){
            $discount = intval($discount * 10);
        } else if ($discountType == Service_Data_Order::DISCOUNT_J) {
            $discount = intval($discount * 100);
        }

        $serviceData = new Service_Data_Order();
        $orderInfo = $serviceData->getOrderById($orderId);
        if (empty($orderInfo)) {
            throw new Zy_Core_Exception(405, "操作失败, 没有查到订单信息");
        }

        if ($orderInfo['is_refund'] == Service_Data_Order::ORDER_DONE || $orderInfo['is_transfer'] == Service_Data_Order::ORDER_DONE) {
            throw new Zy_Core_Exception(405, "操作失败, 已结转或已退款的订单无法调整优惠");
        }

        $serviceSubject = new Service_Data_Subject();
        $subjectInfo = $serviceSubject->getSubjectById(intval($orderInfo['subject_id']));
        if (empty($subjectInfo)) {
            throw new Zy_Core_Exception(405, "操作失败, 科目不存在");
        }

        // 减免, 如果优惠价格>实际价格, 那么不能处理
        if ($discountType == Service_Data_Order::DISCOUNT_J && (intval($subjectInfo['price']) - $discount) < 0) {
            throw new Zy_Core_Exception(405, "操作失败, 减免价格不能超过实际的科目价格");
        }

        $profile = [
            "discount"          => $discount, 
            "discount_type"     => $discountType,
            "update_time"       => time(),
        ];

        $serviceData = new Service_Data_Order();
        $ret = $serviceData->update($orderId, $profile);
        if ($ret == false) {
            throw new Zy_Core_Exception(405, "修改失败, 请重试");
        }
        return array();
    }
}