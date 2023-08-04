<?php

class Service_Page_Order_Create extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $studentUid     = empty($this->request['student_uid']) ? 0 : intval($this->request['student_uid']);
        $subjectId      = empty($this->request['subject_id']) ? 0 : intval($this->request['subject_id']);
        $balance        = empty($this->request['balance']) ? 0 : intval(floatval($this->request['balance']) * 100);
        $discount       = empty($this->request['discount']) ? 0 : floatval($this->request['discount']);
        $discountType   = empty($this->request["discount_type"]) || !in_array($this->request["discount_type"], Service_Data_Order::DISCOUNT_TYPE) ? 0 : intval($this->request['discount_type']);

        if ($studentUid <= 0 || $subjectId <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 学员, 科目为必填项, 不能为空");
        }

        if ($discountType == Service_Data_Order::DISCOUNT_J && ($discount <= 0)) {
            throw new Zy_Core_Exception(405, "操作失败, 减免优惠配置错误, 需要配置具体价格");
        }

        if ($discountType == Service_Data_Order::DISCOUNT_Z && ($discount <= 0 || $discount >= 10)) {
            throw new Zy_Core_Exception(405, "操作失败, 折扣优惠配置错误, 折扣必须在 x>0 并且 x<10 之间, 小数点后一位");
        }

        if ($discountType == 0) {
            $discount = 0;
        } else if ($discountType == Service_Data_Order::DISCOUNT_Z){
            $discount = intval($discount * 10);
        } else if ($discountType == Service_Data_Order::DISCOUNT_J) {
            $discount = intval($discount * 100);
        }

        $serviceProfile = new Service_Data_Profile();
        $studentInfo = $serviceProfile->getUserInfoByUid($studentUid);
        if (empty($studentInfo) || $studentInfo['state'] != Service_Data_Profile::STUDENT_ABLE) {
            throw new Zy_Core_Exception(405, "操作失败, 学员不存在或已下线");
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

        $profile = [
            "subject_id"        => $subjectId, 
            "student_uid"       => $studentUid, 
            "balance"           => $balance, 
            "total_balance"     => $balance,
            "discount"          => $discount, 
            "discount_type"     => $discountType,
            "is_transfer"       => Service_Data_Order::ORDER_ABLE,
            "is_refund"         => Service_Data_Order::ORDER_ABLE,
            'update_time'       => time(),
            'create_time'       => time(),
        ];

        $serviceOrder = new Service_Data_Order();
        $ret = $serviceOrder->create($profile);
        if ($ret == false) {
            throw new Zy_Core_Exception(405, "创建失败, 请重试");
        }
        return array();
    }
}