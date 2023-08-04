<?php

class Service_Page_Order_Transfer_Create extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $type           = empty($this->request['type']) ? 0 : intval($this->request['type']);
        $orderId        = empty($this->request['order_id']) ? 0 : intval($this->request['order_id']);
        $transferId     = empty($this->request['transfer_id']) ? 0 : intval($this->request['transfer_id']);
        $newBalance     = empty($this->request['new_balance']) ? 0 : intval(floatval($this->request['new_balance']) * 100);
        $subjectId      = empty($this->request['subject_id']) ? 0 : intval($this->request['subject_id']);
        $discount       = empty($this->request['discount']) ? 0 : floatval($this->request['discount']);
        $discountType   = empty($this->request["discount_type"]) || !in_array($this->request["discount_type"], Service_Data_Order::DISCOUNT_TYPE) ? 0 : intval($this->request['discount_type']);

        if (!in_array($type, [1,2])) {
            throw new Zy_Core_Exception(405, "操作失败, 结转方式错误, 请联系管理员");
        }

        if ($orderId <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 原订单不能为空");
        }

        if ($type == 1 && $transferId <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 目标订单不能为空");
        }

        $serviceOrder = new Service_Data_Order();
        $orderInfo = $serviceOrder->getOrderById($orderId);
        if (empty($orderInfo)) {
            throw new Zy_Core_Exception(405, "操作失败, 没有查到原订单信息");
        }

        $serviceCurriculum = new Service_Data_Curriculum();
        $curriculum = $serviceCurriculum->getListByConds(array('order_id'=>$orderId,'state'=>Service_Data_Schedule::SCHEDULE_ABLE));
        if (!empty($curriculum)) {
            throw new Zy_Core_Exception(405, "操作失败, 当前订单还有未结算的排课, 无法结转");
        }

        if ($orderInfo['is_transfer'] == Service_Data_Order::ORDER_DONE || $orderInfo['is_refund'] == Service_Data_Order::ORDER_DONE) {
            throw new Zy_Core_Exception(405, "操作失败, 当前订单已结转或已退款");
        }

        if ($orderInfo['balance'] <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 订单已无余额, 无法结转");
        }

        $serviceProfile = new Service_Data_Profile();
        $studentInfo = $serviceProfile->getUserInfoByUid(intval($orderInfo['student_uid']));
        if (empty($studentInfo) || $studentInfo['state'] != Service_Data_Profile::STUDENT_ABLE) {
            throw new Zy_Core_Exception(405, "操作失败, 所属学员不存在或已下线");
        }

        $subjectInfo = $transferOrder = array();
        if ($type == 1) {
            $transferOrder = $serviceOrder->getOrderById($transferId);
            if (empty($transferOrder)) {
                throw new Zy_Core_Exception(405, "操作失败, 结转的目标订单不存在");
            }
            if ($transferOrder['is_transfer'] == Service_Data_Order::ORDER_DONE || $transferOrder['is_refund'] == Service_Data_Order::ORDER_DONE) {
                throw new Zy_Core_Exception(405, "操作失败, 结转的目标订单已结转或已退款");
            }
            if ($transferOrder['student_uid'] != $orderInfo['student_uid']) {
                throw new Zy_Core_Exception(405, "操作失败, 结转目标订单和当前订单分属不同学员, 不能跨学员结转");
            }
        } else {
            if ($subjectId <= 0) {
                throw new Zy_Core_Exception(405, "操作失败, 科目为必填项, 不能为空");
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
    
            $serviceSubject = new Service_Data_Subject();
            $subjectInfo = $serviceSubject->getSubjectById($subjectId);
            if (empty($subjectInfo)) {
                throw new Zy_Core_Exception(405, "操作失败, 科目不存在");
            }
    
            // 减免, 如果优惠价格>实际价格, 那么不能处理
            if ($discountType == Service_Data_Order::DISCOUNT_J && (intval($subjectInfo['price']) - $discount) < 0) {
                throw new Zy_Core_Exception(405, "操作失败, 减免价格不能超过实际的科目价格");
            }
        }

        $profile = [
            "type"              => $type, 
            "order_info"        => $orderInfo, 
            "transfer_info"     => empty($transferOrder) ? array() : $transferOrder, 
            "subject_info"      => $subjectInfo,
            "subject_id"        => $subjectId, 
            "new_balance"       => $newBalance,
            "discount"          => $discount, 
            "discount_type"     => $discountType, 
        ];

        $serviceData = new Service_Data_Transfer();
        $ret = $serviceData->createTransfer($profile);
        if ($ret == false) {
            throw new Zy_Core_Exception(405, "结转失败, 请重试");
        }
        return array();
    }
}