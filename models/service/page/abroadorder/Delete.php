<?php

class Service_Page_Abroadorder_Delete extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $orderId    = empty($this->request['order_id']) ? 0 : intval($this->request['order_id']);
        $apackageId = empty($this->request['apackage_id']) ? 0 : intval($this->request['apackage_id']);
        if ($orderId <= 0 || $apackageId <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 参数错误请重试");
        }

        // check order
        $serviceOrder = new Service_Data_Order();
        $order = $serviceOrder->getAporderById($orderId);
        if (empty($order)) {
            throw new Zy_Core_Exception(405, "操作失败, 要删除的留学计划订单不存在");
        }

        // 获取uid信息
        $serviceUser = new Service_Data_Profile();
        $studentInfo = $serviceUser->getUserInfoByUid(intval($order["student_uid"]));
        if (empty($studentInfo)) {
            throw new Zy_Core_Exception(405, "操作失败, 订单关联的学员不存在, 请检查");
        }
        if (!$this->isOperator(Service_Data_Roles::ROLE_MODE_STUDENT_AMOUNT_HANDLE, $studentInfo['sop_uid'])) {
            throw new Zy_Core_Exception(405, "操作失败, 非学管或特定权限人员, 无权限操作");
        }        
        
        // check package
        $serviceData = new Service_Data_Aporderpackage();
        $apackageInfo = $serviceData->getAbroadpackageById($apackageId);
        if (empty($apackageInfo)) {
            throw new Zy_Core_Exception(405, "操作失败, 服务不存在");
        }
        if ($apackageInfo['state'] != Service_Data_Aporderpackage::APORDER_STATUS_ABLE) {
            throw new Zy_Core_Exception(405, "操作失败, 服务不在有效状态内, 不可删除");
        }

        // check schedule
        $serviceData = new Service_Data_Curriculum();
        $curriculum = $serviceData->getTotalByConds(array('order_id' => $orderId));
        if ($curriculum > 0) {
            throw new Zy_Core_Exception(405, "操作失败, 已绑定课程的订单不能删除");
        }

        // check abroadplan
        $serviceData = new Service_Data_Abroadplan();
        $abroadplanInfo = $serviceData->getAbroadplanById(intval($order['abroadplan_id']));
        if (empty($abroadplanInfo)) {
            throw new Zy_Core_Exception(405, "操作失败, 已绑定的留学计划不存在或被删除, 请联系管理员");
        }

        // 删除
        $ret = $serviceOrder->deleteAporder($orderId, $order, $abroadplanInfo);
        if ($ret == false) {
            throw new Zy_Core_Exception(405, "删除失败");
        }
        return array();
    }
}