<?php

class Service_Page_Order_Refund_Create extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $orderId        = empty($this->request['order_id']) ? 0 : intval($this->request['order_id']);
        $balance        = empty($this->request['balance']) ? 0 : intval(floatval($this->request['balance']) * 100);
        $scheduleNums   = empty($this->request['refund_schedule_nums']) ? 0 : floatval($this->request['refund_schedule_nums']);

        if ($orderId <= 0 || $balance <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 订单不能为空");
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

        $originNums = floatval(sprintf("%.2f", $balance / $orderInfo['price']));
        if ($originNums != $scheduleNums) {
            throw new Zy_Core_Exception(405, "操作失败, 预览课时数和实际退款课时数对不上, 请重新填写");
        }

        $serviceCurriculum = new Service_Data_Curriculum();
        $curriculum = $serviceCurriculum->getListByConds(array('order_id'=>$orderId,'state'=>Service_Data_Schedule::SCHEDULE_ABLE));
        if (!empty($curriculum)) {
            $duration = 0;
            foreach ($curriculum as $item) {
                $duration += $item['end_time'] - $item['start_time'];
            }
            $duration = $duration / 3600;

            if ($duration * $orderInfo['price'] > $orderInfo['balance'] - $balance) {
                throw new Zy_Core_Exception(405, "操作失败, 订单扣除未结算课程费用后的余额 , 不足支持退款");
            }
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
            "balance"           => $balance, 
            "schedule_nums"     => $scheduleNums,
            "operator"          => OPERATOR, 
            'update_time'       => time(),
            'create_time'       => time(),
        ];

        $serviceData = new Service_Data_Refund();
        $ret = $serviceData->create($profile, $orderInfo);
        if ($ret == false) {
            throw new Zy_Core_Exception(405, "退款失败, 请重试");
        }
        return array();
    }
}