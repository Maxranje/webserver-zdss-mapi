<?php
// 完结
class Service_Page_Abroadorder_Package_Done extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $apackageId = empty($this->request['apackage_id']) ? 0 : intval($this->request['apackage_id']);
        if ($apackageId <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 参数错误请重试");
        }

        // check package
        $servicePackage = new Service_Data_Aporderpackage();
        $apackageInfo = $servicePackage->getAbroadpackageById($apackageId);
        if (empty($apackageInfo) || $apackageInfo["state"] != Service_Data_Aporderpackage::APORDER_STATUS_ABLE) {
            throw new Zy_Core_Exception(405, "操作失败, 服务不存在或服务不是有效状态, 无法完结, 请确认服务状态");
        }

        // check student
        $serviceProfile = new Service_Data_Profile();
        $studentInfo = $serviceProfile->getUserInfoByUid(intval($apackageInfo["uid"]));
        if (empty($studentInfo)) {
            throw new Zy_Core_Exception(405, "操作失败, 学员不存在或已下线");
        }        
        if (!$this->isOperator(Service_Data_Roles::ROLE_MODE_STUDENT_AMOUNT_HANDLE, $studentInfo['sop_uid'])) {
            throw new Zy_Core_Exception(405, "操作失败, 非学管或特定权限人员, 无权限操作");
        }        

        $serviceData = new Service_Data_Abroadplan();
        $abroadplanInfo = $serviceData->getAbroadplanById(intval($apackageInfo['abroadplan_id']));
        if (empty($abroadplanInfo)) {
            throw new Zy_Core_Exception(405, "操作失败, 留学计划不存在, 无法删除");
        }        

        // check order
        $serviceData = new Service_Data_Order();
        $orderDatas = $serviceData->getAporderByPackageId($apackageId);
        $orderIds = Zy_Helper_Utils::arrayInt($orderDatas, "order_id");
        if (!empty($orderIds)) {
            // check schedule
            $serviceData = new Service_Data_Curriculum();
            $orderCount = $serviceData->getTotalByConds(array(
                sprintf("order_id in (%s)", implode(",", $orderIds)),
                "state" => Service_Data_Schedule::SCHEDULE_ABLE,
            ));
            if ($orderCount > 0) {
                throw new Zy_Core_Exception(405, "操作失败, 服务所关联的订单还存在待结算课程, 无法完结");
            }
        }

        $serviceData = new Service_Data_Apackageconfirm();
        $confirmData = $serviceData->getConfirmById(intval($apackageId));
        if (empty($confirmData)) {
            throw new Zy_Core_Exception(405, "操作失败, 服务没有检查项, 无法完结");
        }   
        foreach ($confirmData["content"] as $v) {
            foreach ($v["items"] as $vv) {
                if (empty($vv["is_oc"]) || empty($vv["is_sc"])) {
                    throw new Zy_Core_Exception(405, "操作失败, 服务检查项未完成,无法完结!");
                }
            }
        }
        
        // 更新
        $porfile = array(
            "state" => Service_Data_Aporderpackage::APORDER_STATUS_DONE_PEND,
            "update_time" => time(),
        );
        $ret = $servicePackage->done($apackageId, intval($apackageInfo["uid"]), $porfile);
        if (!$ret) {
            throw new Zy_Core_Exception(405, "更新失败, 请重试");
        }
        
        return array();
    }
}