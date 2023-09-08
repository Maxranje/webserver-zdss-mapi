<?php

class Service_Page_Order_Review extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $studentUid     = empty($this->request['student_uid']) ? 0 : intval($this->request['student_uid']);
        $subjectId      = empty($this->request['subject_id']) ? 0 : intval($this->request['subject_id']);
        $scheduleNums   = empty($this->request['schedule_nums']) ? 0 : floatval($this->request['schedule_nums']);
        $discount       = empty($this->request['discount']) ? 0 : floatval($this->request['discount']);
        $discountType   = empty($this->request["discount_type"]) || !in_array($this->request["discount_type"], Service_Data_Order::DISCOUNT_TYPE) ? 0 : intval($this->request['discount_type']);

        if ($studentUid <= 0 || $subjectId <= 0 || $scheduleNums <= 0) {
            return array(); 
        }

        if ($discountType == Service_Data_Order::DISCOUNT_J && ($discount <= 0)) {
            return array();
        }

        if ($discountType == Service_Data_Order::DISCOUNT_Z && ($discount <= 0 || $discount >= 10)) {
            return array();
        }

        $serviceSubject = new Service_Data_Subject();
        $subjectInfo = $serviceSubject->getSubjectById($subjectId);
        if (empty($subjectInfo)) {
            return array();
        }

        $originPrice = $realPrice = intval($subjectInfo['price']);
        $originBalance = $realBalance = intval($subjectInfo['price'] * $scheduleNums);

        if ($discountType == 0) {
            $discount = 0;
        } else if ($discountType == Service_Data_Order::DISCOUNT_Z){
            $discount = intval($discount * 10);
            $realPrice = intval($realPrice / 100 * $discount);
            $realBalance = $realPrice * $scheduleNums;
        } else if ($discountType == Service_Data_Order::DISCOUNT_J) {
            $discount = intval($discount * 100);
            $realBalance = $realBalance - $discount;
            $realPrice = intval($realBalance / $scheduleNums);
        }

        return array(
            "origin_price" => sprintf("%.2f", $originPrice / 100),
            "origin_balance" => sprintf("%.2f", $originBalance / 100),
            "real_price" => sprintf("%.2f", $realPrice / 100),
            "real_balance" => sprintf("%.2f", $realBalance / 100),
        );
    }
}