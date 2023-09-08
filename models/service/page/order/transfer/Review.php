<?php

class Service_Page_Order_Transfer_Review extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $orderId        = empty($this->request['order_id']) ? 0 : intval($this->request['order_id']);
        $balance        = empty($this->request['balance']) ? 0 : intval(floatval($this->request['balance']) * 100);
        $studentUid     = empty($this->request['student_uid']) ? 0 : intval($this->request['student_uid']);
        $subjectId      = empty($this->request['subject_id']) ? 0 : intval($this->request['subject_id']);
        $discount       = empty($this->request['discount']) ? 0 : floatval($this->request['discount']);
        $discountType   = empty($this->request["discount_type"]) || !in_array($this->request["discount_type"], Service_Data_Order::DISCOUNT_TYPE) ? 0 : intval($this->request['discount_type']);

        if ($studentUid <= 0 || $subjectId <= 0 || $balance <=0 ) {
            return array();
        }

        if ($discountType == Service_Data_Order::DISCOUNT_J && ($discount <= 0)) {
            return array();
        }

        if ($discountType == Service_Data_Order::DISCOUNT_Z && ($discount <= 0 || $discount >= 10)) {
            return array();
        }
        
        $orderInfo = array();
        if ($orderId > 0) {
            $serviceOrder = new Service_Data_Order();
            $orderInfo = $serviceOrder->getOrderById($orderId);
        }

        if ($orderInfo['balance'] < $balance) {
            throw new Zy_Core_Exception(405, "配置余额超过了待结转订单余额, 无法生成预览数据"); 
        }

        $serviceSubject = new Service_Data_Subject();
        $subjectInfo = $serviceSubject->getSubjectById($subjectId);
        if (empty($subjectInfo)) {
            throw new Zy_Core_Exception(405,  "获取科目信息失败, 请重试"); 
        }

        $originPrice = $realPrice = intval($subjectInfo['price']);
        $originBalance = $realBalance = $balance;
        $scheduleNums = 0;

        if ($discountType == 0) {
            $scheduleNums = sprintf("%.2f", $realBalance / $realPrice);
            $discount = 0;
        } else if ($discountType == Service_Data_Order::DISCOUNT_Z){
            $discount = intval($discount * 10);
            $realPrice = intval($realPrice / 100 * $discount);
            $originBalance = $originBalance / $discount * 100;
            $scheduleNums = sprintf("%.2f", $realBalance / $realPrice);
        } else if ($discountType == Service_Data_Order::DISCOUNT_J) {
            $discount = intval($discount * 100);
            $originBalance = $originBalance + $discount;
            $scheduleNums = sprintf("%.2f", $originBalance / $originPrice);
            $realPrice = $realBalance / $scheduleNums;
        }

        return array(
            "transfer_origin_price" => sprintf("%.2f", $originPrice / 100),
            "transfer_origin_balance" => sprintf("%.2f", $originBalance / 100),
            "transfer_real_price" => sprintf("%.2f", $realPrice / 100),
            "transfer_real_balance" => sprintf("%.2f", $realBalance / 100),
            "transfer_schedule_nums" => $scheduleNums,
        );
    }
}