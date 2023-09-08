<?php

class Service_Page_Order_Transfer_Create extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $orderId        = empty($this->request['order_id']) ? 0 : intval($this->request['order_id']);
        $balance        = empty($this->request['balance']) ? 0 : intval(floatval($this->request['balance']) * 100);
        $subjectId      = empty($this->request['subject_id']) ? 0 : intval($this->request['subject_id']);
        $discount       = empty($this->request['discount']) ? 0 : floatval($this->request['discount']);
        $discountType   = empty($this->request["discount_type"]) || !in_array($this->request["discount_type"], Service_Data_Order::DISCOUNT_TYPE) ? 0 : intval($this->request['discount_type']);

        $originPrice    = empty($this->request['transfer_origin_price']) ? 0 : intval(floatval($this->request['transfer_origin_price']) * 100);
        $originBalance  = empty($this->request['transfer_origin_balance']) ? 0 : intval(floatval($this->request['transfer_origin_balance']) * 100);
        $realPrice      = empty($this->request['transfer_real_price']) ? 0 : intval(floatval($this->request['transfer_real_price']) * 100);
        $realBalance    = empty($this->request['transfer_real_balance']) ? 0 : intval(floatval($this->request['transfer_real_balance']) * 100);
        $scheduleNums   = empty($this->request['transfer_schedule_nums']) ? 0 : floatval($this->request['transfer_schedule_nums']);


        if ($orderId <= 0 || $balance <= 0 || $subjectId <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 原订单不能为空");
        }

        if ($originPrice <= 0 || $originBalance <= 0 || $realPrice <= 0 || $realBalance <=0 || $scheduleNums <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 未生成预览信息, 无法直接结转");
        }

        if ($discountType ==  Service_Data_Order::DISCOUNT_J && ($discount <= 0)) {
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

        $serviceOrder = new Service_Data_Order();
        $orderInfo = $serviceOrder->getOrderById($orderId);
        if (empty($orderInfo) || $orderInfo['balance'] < $balance) {
            throw new Zy_Core_Exception(405, "操作失败, 没有查到原订单信息或原订单存额不足当前填写结转金额");
        }

        if ($orderInfo['balance'] <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 订单已无余额, 无法结转");
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
                throw new Zy_Core_Exception(405, "操作失败, 订单扣除未结算课程费用后的余额 , 不足支持结转");
            }
        }

        $serviceProfile = new Service_Data_Profile();
        $studentInfo = $serviceProfile->getUserInfoByUid(intval($orderInfo['student_uid']));
        if (empty($studentInfo) || $studentInfo['state'] != Service_Data_Profile::STUDENT_ABLE) {
            throw new Zy_Core_Exception(405, "操作失败, 所属学员不存在或已下线");
        }

        $serviceSubject = new Service_Data_Subject();
        $subjectInfo = $serviceSubject->getSubjectById($subjectId);
        if (empty($subjectInfo) || !empty($subjectInfo['parent_id'])) {
            throw new Zy_Core_Exception(405, "操作失败, 科目不是一个一级分类或不存在");
        }

        if ($subjectInfo['price'] != $originPrice) {
            throw new Zy_Core_Exception(405, "操作失败, 预览数据变更, 与实际不符合");
        }

        $profile = [
            "subject_id"        => $subjectId, 
            "student_uid"       => intval($orderInfo['student_uid']), 
            "balance"           => $realBalance, 
            "price"             => $realPrice,
            "discount"          => $discount, 
            "discount_type"     => $discountType,
            "transfer_id"       => $orderId,
            "operator"          => OPERATOR,
            'update_time'       => time(),
            'create_time'       => time(),
            "ext"               => json_encode(array(
                "origin_balance"    => $originBalance, 
                "origin_price"      => $originPrice,
                "real_balance"      => $realBalance, 
                "real_price"        => $realPrice,
                "schedule_nums"     => $scheduleNums,
                "transfer_balance"  => 0,
                "refund_balance"    => 0,
            )),
        ];

        $serviceOrder = new Service_Data_Transfer();
        $ret = $serviceOrder->create($profile, $orderInfo);
        if ($ret == false) {
            throw new Zy_Core_Exception(405, "创建失败, 请重试");
        }
        return array();
    }
}