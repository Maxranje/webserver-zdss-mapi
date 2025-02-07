<?php

class Service_Page_Order_Detail extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $orderId = empty($this->request['order_id']) ? 0 : intval($this->request['order_id']);

        // 方式service模块不刷新, 直接返回空
        $emptyResult = array(
            "order_id" => "",
            "student_name" => "",
            "subject_name" => "",
            "clasze_name" => "",
            "birthplace" => "",
            "student_uid" => "",
            "update_time" => "",
            "create_time" => "",
            "pic_name" => "",
            "origin_balance" => "",
            "real_balance" => "",
            "origin_price" => "",
            "real_price" => "",
            "schedule_nums" => "",
            "change_balance" => "",
            "discount_info" => "",
            "able_balance" => "",
            "able_duration" => "",
        );

        if ($orderId <= 0) {
            return $emptyResult;
        }

        $serviceData = new Service_Data_Order();
        $orderInfo = $serviceData->getNmorderById($orderId);
        if (empty($orderInfo)) {
            return $emptyResult;
        }   
        
        return $this->formatDefault($orderInfo);
    }

    // 默认格式化
    private function formatDefault ($order) {

        $serviceData = new Service_Data_Subject();
        $subjectInfo = $serviceData->getSubjectById(intval($order['subject_id']));

        $serviceData = new Service_Data_Birthplace();
        $birthplace = $serviceData->getBirthplaceById(intval($order['bpid']));

        $serviceData = new Service_Data_Clasze();
        $claszeInfo = $serviceData->getClaszeById(intval($order['cid']));

        $serviceData = new Service_Data_Profile();
        $studentInfo = $serviceData->getUserInfoByUid(intval($order['student_uid']));

        // 排课数
        $serviceData = new Service_Data_Curriculum();
        $orderCounts = $serviceData->getScheduleTimeCountByOrder(array(intval($order['order_id'])));

        if (empty($subjectInfo['name']) || empty($claszeInfo['name']) || empty($birthplace['name'])) {
            return array();
        }

        if (empty($studentInfo['nickname'])) {
            return array();
        }

        $extra = json_decode($order['ext'], true);

        $item = array();
        $item['order_id']       = $order['order_id'] ;
        $item['student_name']   = $studentInfo['nickname'];
        $item['subject_name']   = $subjectInfo['name'];
        $item['clasze_name']    = $claszeInfo['name'];
        $item['birthplace']     = $birthplace['name'];
        $item['student_uid']    = intval($order['student_uid']);
        $item['update_time']    = date("Y年m月d日 H:i",$order['update_time']);
        $item['create_time']    = date("Y年m月d日 H:i",$order['create_time']);

        if ($order['isfree'] == 1) {
            $item['pic_name'] = "免费";
        } else {
            $item['pic_name'] = "";
        }

        $item['origin_balance'] = sprintf("%.2f", $extra['origin_balance'] / 100);
        $item['real_balance']   = sprintf("%.2f", $extra['real_balance'] / 100);
        $item['origin_price']   = sprintf("%.2f", $extra['origin_price'] / 100);
        $item['real_price']     = sprintf("%.2f", $extra['real_price'] / 100);
        $item['schedule_nums']  = $extra['schedule_nums'];
        $item['change_balance'] = empty($extra['change_balance']) ? "0.00" : sprintf("%.2f", $extra['change_balance'] / 100);
        
        $item['discount_info']  = "";
        if (!empty($order['discount_z'])) {
            $item['discount_info'] .= "折扣(" . $order['discount_z'] . "%)  ";
        } 
        if (!empty($order['discount_j'])) {
            $item['discount_info'] .= sprintf("减免(%.2f元)", $order['discount_j'] / 100);
        }
        $item['discount_info'] = empty($item['discount_info']) ? "-" : $item['discount_info'];

        // 余额减去待结算的
        $item['able_balance'] = $order['balance'];
        if ($orderCounts[$order['order_id']]['u'] > 0 ) {
            $item['able_balance'] = $order['balance'] - ($orderCounts[$order['order_id']]['u'] * $order['price']);
        }
        
        $item['able_duration'] = Zy_Helper_Utils::FloadtoStringNoRound($item['able_balance'] / $order['price']);
        $item['able_balance']  = Zy_Helper_Utils::FloadtoStringNoRound($item['able_balance'] / 100);
        return  $item;
    }
}