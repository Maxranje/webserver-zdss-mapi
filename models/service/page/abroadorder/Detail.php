<?php

class Service_Page_Abroadorder_Detail extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $orderId = empty($this->request['order_id']) ? 0 : intval($this->request['order_id']);

        // 方式service模块不刷新, 直接返回空
        $emptyResult = array(
            "order_id" => "",
            "student_name" => "",
            "abroadplan_name" => "",
            "subject_name" => "",
            "clasze_name" => "",
            "birthplace" => "",
            "student_uid" => "",
            "update_time" => "",
            "create_time" => "",
            "schedule_nums" => "",
            "band_duration" => "",
            "check_duration" => "",
            "uncheck_duration" => "",
            "apackage_state" => 0,
        );

        if ($orderId <= 0) {
            return $emptyResult;
        }

        $serviceData = new Service_Data_Order();
        $orderData = $serviceData->getAporderById($orderId);
        if (empty($orderData)) {
            return $emptyResult;
        }
        $orderData['ext'] = json_decode($orderData['ext'], true);
        if (empty($orderData['ext']["schedule_nums"])) {
            return $emptyResult;
        }

        // userInfo
        $serviceData = new Service_Data_Profile();
        $userInfo = $serviceData->getUserInfoByUid(intval($orderData['student_uid']));
        if (empty($userInfo)) {
            return $emptyResult;
        }
        if (!$this->isOperator(Service_Data_Roles::ROLE_MODE_STUDENT_AMOUNT_HANDLE, intval($userInfo["sop_uid"]))) {
            return $emptyResult;
        }

        // check apackage
        $serviceData = new Service_Data_Aporderpackage();
        $apackageInfo = $serviceData->getAbroadpackageById(intval($orderData['apackage_id']));
        if (empty($apackageInfo)) {
            return $emptyResult;
        }

        // check abroadplan
        $serviceData = new Service_Data_Abroadplan();
        $abroadplanInfo = $serviceData->getAbroadplanById(intval($orderData['abroadplan_id']));
        if (empty($abroadplanInfo)) {
            return $emptyResult;
        }

        //  check subject
        $serviceData = new Service_Data_Subject();
        $subjectInfo = $serviceData->getSubjectById(intval($orderData['subject_id']));        
        if (empty($subjectInfo)) {
            return $emptyResult;
        }

        // check birthplace
        $serviceData = new Service_Data_Birthplace();
        $birthplace = $serviceData->getBirthplaceById(intval($orderData['bpid']));
        if (empty($birthplace)) {
            return $emptyResult;
        }

        // claszeinfo
        $serviceData = new Service_Data_Clasze();
        $claszeInfo = $serviceData->getClaszeById(intval($orderData['cid']));
        if (empty($claszeInfo)) {
            return $emptyResult;
        }

        // 排课数
        $serviceData = new Service_Data_Curriculum();
        $orderCount = $serviceData->getScheduleTimeCountByOrder(array($orderId));
        $orderCount = $orderCount[$orderId];

        // 输出数据
        $result = $emptyResult;
        $result['order_id']         = $orderId;
        $result['student_name']     = $userInfo['nickname'];
        $result["abroadplan_name"]  = $abroadplanInfo["name"];
        $result['subject_name']     = $subjectInfo['name'];
        $result['clasze_name']      = $claszeInfo['name'];
        $result['birthplace']       = $birthplace['name'];
        $result['student_uid']      = intval($orderData['student_uid']);
        $result['update_time']      = date("Y年m月d日 H:i",$orderData['update_time']);
        $result['create_time']      = date("Y年m月d日 H:i",$orderData['create_time']);
        $result['schedule_nums']    = $orderData['ext']['schedule_nums'];
        $result['band_duration']    = empty($orderCount['a']) ? "0" : floatval(sprintf("%.2f", $orderCount['a']));
        $result['check_duration']   = empty($orderCount['c']) ? "0" : floatval(sprintf("%.2f", $orderCount['c']));
        $result['uncheck_duration'] = empty($orderCount['u']) ? "0" : floatval(sprintf("%.2f", $orderCount['u']));
        $result['apackage_state']   = intval($apackageInfo['state']);
        return  $result;
    }
}