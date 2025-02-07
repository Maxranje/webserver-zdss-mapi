<?php

class Service_Page_Abroadorder_Package_Duration extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $apackageId     = empty($this->request['apackage_id']) ? 0 : intval($this->request['apackage_id']);
        $duration       = empty($this->request['duration']) ? 0 : floatval($this->request['duration']);
        $remark         = empty($this->request['add_duration_remark']) ? "" : trim($this->request['add_duration_remark']);

        if ($apackageId <= 0 || $duration <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 服务包id不存在, 同时课时数必须大于0");
        }

        if (mb_strlen($remark) > 100) {
            throw new Zy_Core_Exception(405, "操作失败, 备注限定100字內");
        }

        // check info 
        $servicePackage = new Service_Data_Aporderpackage();
        $apackageInfo = $servicePackage->getAbroadpackageById($apackageId);
        if (empty($apackageInfo) || $apackageInfo["state"] != Service_Data_Aporderpackage::APORDER_STATUS_ABLE) {
            throw new Zy_Core_Exception(405, "操作失败, 服务不存在, 或服务不是有效状态, 无法加课时");
        }

        // check student info
        $serviceProfile = new Service_Data_Profile();
        $studentInfo = $serviceProfile->getUserInfoByUid(intval($apackageInfo['uid']));
        if (empty($studentInfo) || $studentInfo['state'] == Service_Data_Profile::STUDENT_DISABLE) {
            throw new Zy_Core_Exception(405, "操作失败, 学员不存在或已下线");
        }      
        if (!$this->isOperator(Service_Data_Roles::ROLE_MODE_STUDENT_AMOUNT_HANDLE, $studentInfo["sop_uid"])) {
            throw new Zy_Core_Exception(405, "操作失败, 非学管和金额权限角色, 其他角色无权限操作");
        }

        // add schedule nums
        $profile = [
            "student_uid"   => intval($apackageInfo["uid"]), 
            "apackage_id"   => $apackageId, 
            "remark"        => $remark, 
            "schedule_nums" => $duration, 
        ];

        $ret = $servicePackage->apackageDurationAdd($profile);
        if ($ret == false) {
            throw new Zy_Core_Exception(405, "创建添加课时审批单失败, 请重试");
        }
        return array();
    }
}