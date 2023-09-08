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
        $orderCounts = $serviceData->getScheduleTimeCountByOrder(array(intval($order['order_id'])));

        if (empty($subjectInfo['name'])) {
            return array(1);
        }

        if (empty($studentInfo['nickname'])) {
            return array(2);
        }

        $extra = json_decode($order['ext'], true);

        $item = array();
        $item['order_id']       = $order['order_id'] ;
        $item['student_name']   = $studentInfo['nickname'];
        $item['subject_name']   = $subjectInfo['name'];
        $item['student_uid']    = intval($order['student_uid']);
        $item['update_time']    = date("Y年m月d日 H:i",$order['update_time']);
        $item['create_time']    = date("Y年m月d日 H:i",$order['create_time']);

        $item['origin_balance'] = sprintf("%.2f", $extra['origin_balance'] / 100);
        $item['real_balance']   = sprintf("%.2f", $extra['real_balance'] / 100);
        $item['origin_price']   = sprintf("%.2f", $extra['origin_price'] / 100);
        $item['real_price']     = sprintf("%.2f", $extra['real_price'] / 100);
        $item['schedule_nums']  = $extra['schedule_nums'];
        $item['transfer_balance']   = empty($extra['transfer_balance']) ? "0.00" : sprintf("%.2f", $extra['transfer_balance'] / 100);
        $item['refund_balance']     = empty($extra['refund_balance']) ? "0.00" : sprintf("%.2f", $extra['refund_balance'] / 100);
        
        $item['discount_info']  = "-";
        if ($order['discount_type'] == Service_Data_Order::DISCOUNT_Z) {
            $item['discount_info'] = "折扣(" . $order['discount'] . "%)";
        } else if ($order['discount_type'] == Service_Data_Order::DISCOUNT_J) {
            $item['discount_info'] = sprintf("减免(%.2f元)", $order['discount'] / 100);
        }

        $item['band_duration']      = sprintf("%.2f", $orderCounts[$order['order_id']]['a']);
        $item['band_balance']       = sprintf("%.2f", ($orderCounts[$order['order_id']]['a'] * $order['price']) / 100);

        // 余额减去待结算的
        $item['able_balance'] = $order['balance'];
        if ($orderCounts[$order['order_id']]['u'] > 0 ) {
            $item['able_balance'] = $order['balance'] - ($orderCounts[$order['order_id']]['u'] * $order['price']);
        }
        
        $item['able_duration'] = sprintf("%.2f", $item['able_balance'] / $order['price']);
        $item['able_balance']  = sprintf("%.2f", $item['able_balance'] / 100);
        return  $item;
    }
}