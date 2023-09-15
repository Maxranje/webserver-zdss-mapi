<?php

class Service_Page_Order_Review extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $studentUid     = empty($this->request['student_uid']) ? 0 : intval($this->request['student_uid']);
        $subjectId      = empty($this->request['subject_id']) ? 0 : intval($this->request['subject_id']);
        $scheduleNums   = empty($this->request['schedule_nums']) ? 0 : floatval($this->request['schedule_nums']);
        $discountZ      = empty($this->request['discount_z']) ? 0 : intval($this->request['discount_z'] * 10);
        $discountJ      = empty($this->request['discount_j']) ? 0 : intval($this->request['discount_j'] * 100);

        if ($studentUid <= 0 || $subjectId <= 0 || $scheduleNums <= 0) {
            return array(); 
        }

        if ($discountJ < 0) {
            return array();
        }

        if ($discountZ < 0 || $discountZ >= 100) {
            return array();
        }

        $serviceSubject = new Service_Data_Subject();
        $subjectInfo = $serviceSubject->getSubjectById($subjectId);
        if (empty($subjectInfo)) {
            return array();
        }

        $originPrice = $realPrice = intval($subjectInfo['price']);
        $originBalance = $realBalance = intval($subjectInfo['price'] * $scheduleNums);

        if ($discountZ > 0){
            $realBalance = ($realBalance * $discountZ) / 100;
        } 
        if ($discountJ > 0) {
            $realBalance = $realBalance - $discountJ;
        }
        $realPrice = intval($realBalance / $scheduleNums);

        return array(
            "origin_price" => sprintf("%.2f", $originPrice / 100),
            "origin_balance" => sprintf("%.2f", $originBalance / 100),
            "real_price" => sprintf("%.2f", $realPrice / 100),
            "real_balance" => sprintf("%.2f", $realBalance / 100),
        );
    }
}