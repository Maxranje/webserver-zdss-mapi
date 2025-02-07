<?php

class Service_Page_Abroadorder_Update extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $orderId        = empty($this->request['order_id']) ? 0 : intval($this->request['order_id']);
        $apackageId     = empty($this->request['apackage_id']) ? 0 : intval($this->request['apackage_id']);
        $scheduleNums   = empty($this->request['update_schedule_nums']) ? 0 : intval($this->request['update_schedule_nums']);

        if ($orderId <= 0 || $apackageId <= 0 || $scheduleNums == 0) {
            throw new Zy_Core_Exception(405, "操作失败, 参数错误请重试");
        }

        // check apackage
        $serviceData = new Service_Data_Aporderpackage();
        $apackageInfo = $serviceData->getAbroadpackageById($apackageId);
        if (empty($apackageInfo)) {
            throw new Zy_Core_Exception(405, "操作失败, 服务不存在");
        }
        if ($apackageInfo['state'] != Service_Data_Aporderpackage::APORDER_STATUS_ABLE) {
            throw new Zy_Core_Exception(405, "操作失败, 服务不在有效状态内, 不可更新课时");
        }

        // check student
        $serviceData = new Service_Data_Profile();
        $userInfo = $serviceData->getUserInfoByUid(intval($apackageInfo["uid"]));
        if (empty($userInfo)) {
            throw new Zy_Core_Exception(405, "操作失败, 订单所对应学员信息不存在, 请检查");
        }
        if (!$this->isOperator(Service_Data_Roles::ROLE_MODE_STUDENT_AMOUNT_HANDLE, intval($userInfo["sop_uid"]))) {
            throw new Zy_Core_Exception(405, "操作失败, 非学管或赋予特定权限, 无法操作");
        }
        
        // check abroadplan 
        $serviceData = new Service_Data_Abroadplan();
        $abroadplanInfo = $serviceData->getAbroadplanById(intval($apackageInfo['abroadplan_id']));
        if (empty($abroadplanInfo)) {
            throw new Zy_Core_Exception(405, "操作失败, 已绑定的留学计划不存在或被删除, 请联系管理员");
        }        

        // check order
        $serviceOrder = new Service_Data_Order();
        $orderData = $serviceOrder->getAporderById($orderId);
        if (empty($orderData)) {
            throw new Zy_Core_Exception(405, "操作失败, 要删除的订单不存在");
        }
        $orderData['ext'] = json_decode($orderData['ext'], true);
        $orderDuration = empty($orderData['ext']['schedule_nums']) ? 0 : $orderData['ext']['schedule_nums'];

        // check duration
        if ($scheduleNums < 0) {
            $serviceData = new Service_Data_Curriculum();
            $orderCount = $serviceData->getScheduleTimeCountByOrder(array($orderId));
            $orderCount = $orderCount[$orderId];
            if ($orderDuration + $scheduleNums < 0) {
                throw new Zy_Core_Exception(405, "操作失败, 订单课时调整后必须大于0");
            }
            if ($orderDuration + $scheduleNums < $orderCount['a']) {
                throw new Zy_Core_Exception(405, "操作失败, 订单课时不足 (订单课时 - 已绑定课时 - 配置课时 < 0)");
            }
        } else if ($scheduleNums > 0){
            $apackageCount = $serviceOrder->getAporderDurationByApackageIds(array($apackageId));
            if ($apackageCount[$apackageId] + $scheduleNums > $apackageInfo['schedule_nums']) {
                throw new Zy_Core_Exception(405, "操作失败, 服务课时不足 (服务所有订单总课时+ 订单配置课时 > 服务配置总课时)");
            }
        }

        $orderData['ext']["schedule_nums"] += $scheduleNums;
        $profile = array(
            "order"         => $orderData,
            "abroadplan"    => $abroadplanInfo,
            "schedule_nums" => $scheduleNums,

        );
        $ret = $serviceOrder->updateAporder($orderId, $profile);
        if (!$ret) {
            throw new Zy_Core_Exception(405, "调整失败, 请重试");
        }
        
        return array();
    }
}