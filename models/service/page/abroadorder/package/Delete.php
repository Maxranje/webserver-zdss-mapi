<?php

class Service_Page_Abroadorder_Package_Delete extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $apackageId = empty($this->request['apackage_id']) ? 0 : intval($this->request['apackage_id']);
        if ($apackageId <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 参数错误请重试");
        }

        // apackage
        $servicePackage = new Service_Data_Aporderpackage();
        $apackageInfo = $servicePackage->getAbroadpackageById($apackageId);
        if (empty($apackageInfo) || !in_array($apackageInfo["state"], [
            Service_Data_Aporderpackage::APORDER_STATUS_ABLE,
            Service_Data_Aporderpackage::APORDER_STATUS_ABLE_REFUES
        ])) {
            throw new Zy_Core_Exception(405, "操作失败, 服务只有在有效或创建审核拒绝2中状态下才可以删除, 请检查");
        }

        // check student
        $serviceProfile = new Service_Data_Profile();
        $studentInfo = $serviceProfile->getUserInfoByUid(intval($apackageInfo["uid"]));
        if (empty($studentInfo)) {
            throw new Zy_Core_Exception(405, "操作失败, 学员不存在");
        }        
        if (!$this->isOperator(Service_Data_Roles::ROLE_MODE_STUDENT_AMOUNT_HANDLE, $studentInfo['sop_uid'])) {
            throw new Zy_Core_Exception(405, "操作失败, 非学管或特定权限人员, 无权限操作");
        }
      
        $serviceData = new Service_Data_Order();
        $orderCount = $serviceData->getTotalByConds(array(
            'apackage_id' => $apackageId, 
            "type" => Service_Data_Order::ORDER_TYPE_ABROADPLAN
        ));
        if ($orderCount > 0) {
            throw new Zy_Core_Exception(405, "操作失败, 服务存在有效订单, 需要删除关联订单才能删除服务");
        }

        $serviceData = new Service_Data_Abroadplan();
        $abroadplanInfo = $serviceData->getAbroadplanById(intval($apackageInfo['abroadplan_id']));
        if (empty($abroadplanInfo)) {
            throw new Zy_Core_Exception(405, "操作失败, 留学计划不存在, 无法删除");
        }        

        // 删除
        $ret = $servicePackage->delete($apackageId, $apackageInfo, $abroadplanInfo);
        if ($ret == false) {
            throw new Zy_Core_Exception(405, "删除失败");
        }
        return array();
    }
}