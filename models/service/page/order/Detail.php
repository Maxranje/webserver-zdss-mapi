<?php

class Service_Page_Order_Detail extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $orderId = empty($this->request['order_id']) ? 0 : intval($this->request['order_id']);

        if ($orderId <= 0) {
            return array();
        }

        $serviceData = new Service_Data_Order();
        $orderInfo = $serviceData->getOrderById($orderId);
        if (empty($orderInfo)) {
            return array();
        }   
        
        return $this->formatDefault($orderInfo);
    }

    // 默认格式化
    private function formatDefault ($order) {

        $serviceData = new Service_Data_Subject();
        $subjectInfo = $serviceData->getSubjectById(intval($order['subject_id']));

        $serviceData = new Service_Data_Profile();
        $studentInfo = $serviceData->getUserInfoByUid(intval($order['student_uid']));

        // 排课数
        $serviceData = new Service_Data_Curriculum();
        $orderCount  = $serviceData->getScheduleTimeCountByOrder(array($order['order_id']));
        $orderCount  = empty($orderCount[$order['order_id']]['able_count']) ? 0 : intval($orderCount[$order['order_id']]['able_count']);

        if (empty($subjectInfo['name'])) {
            return array();
        }

        if (empty($studentInfo['nickname'])) {
            return array();
        }

        $result = array();
        $result['order_id']         = $order['order_id'] ;
        $result['student_name']     = $studentInfo['nickname'];
        $result['subject_name']     = $subjectInfo['name'];
        $result['subject_price']    = sprintf("%.2f", $subjectInfo['price'] / 100);
        $result['pic_name']         = mb_substr($result['subject_name'], 0, 1);
        $result['discount_type']    = $order['discount_type'];
        $result['is_transfer']      = intval($order['is_transfer']);
        $result['is_refund']        = intval($order['is_refund']);

        $result['schedule_count'] = "0";
        $result['schedule_money'] = "0.00";

        // y优惠数据
        $discountType = "-";
        $discountPrice = 0;
        if ($order['discount_type'] == Service_Data_Order::DISCOUNT_Z) {
            $discountPrice = intval($subjectInfo['price']) * ((100 - intval($order['discount'])) / 100);
            $discountType  = sprintf("%s折", intval($order['discount']) / 10);
        } else if ($order['discount_type'] == Service_Data_Order::DISCOUNT_J) {
            $discountPrice = intval($order['discount']);
            $discountType  = "减免";
        }
        $result['discount_type']    = $discountType;
        $result['discount_price']   = sprintf("%.2f", $discountPrice/100);

        // 余下的价格,  余下的余额, 余下的可排的可课时
        $realPrice  = intval($subjectInfo['price']) - $discountPrice;
        $balance    = intval($order['balance']) - ($orderCount * $realPrice);
        $balance    = $balance <= 0 ? 0 : $balance;

        $result['balance']          = sprintf("%.2f", $balance / 100);
        $result['schedule_count']   = sprintf("%.2f", $balance / $realPrice);
        return $result;
    }
}