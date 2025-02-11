<?php

class Service_Page_Abroadorder_Package_Review extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $studentUid     = empty($this->request['student_uid']) ? 0 : intval($this->request['student_uid']);
        $abroadplanId   = empty($this->request['abroadplan_id']) ? 0 : intval($this->request['abroadplan_id']);
        $discountZ      = empty($this->request['discount_z']) ? 0 : intval(floatval($this->request['discount_z']) * 10);
        $discountJ      = empty($this->request['discount_j']) ? 0 : intval(floatval($this->request['discount_j']) * 100);

        if ($studentUid <= 0 || $abroadplanId <= 0) {
            return array(); 
        }

        if ($discountJ < 0) {
            return array();
        }

        if ($discountZ < 0 || $discountZ >= 100) {
            return array();
        }

        $serviceData = new Service_Data_Abroadplan();
        $abroadplanInfo = $serviceData->getAbroadplanById($abroadplanId);
        if (empty($abroadplanInfo)) {
            return array();
        }

        $originBalance = $realBalance = intval($abroadplanInfo['price']);
        $scheduleNums = $abroadplanInfo["duration"];

        if ($discountZ > 0){
            $realBalance = ($realBalance * $discountZ) / 100;
        } 
        if ($discountJ > 0) {
            $realBalance = $realBalance - $discountJ;
        }

        return array(
            "origin_balance" => sprintf("%.2f", $originBalance / 100),
            "real_balance" => sprintf("%.2f", $realBalance / 100),
            "schedule_nums" => $scheduleNums,
        );
    }
}