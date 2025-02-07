<?php
// 服务订单解绑全部未结算的
class Service_Page_Abroadorder_Unbind extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin() ) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $apackageId = empty($this->request['apackage_id']) ? 0 : intval($this->request['apackage_id']);
        if ($apackageId <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 参数错误请重试");
        }

        // check apackage
        $serviceData = new Service_Data_Aporderpackage();
        $apackageInfo = $serviceData->getAbroadpackageById($apackageId);
        if (empty($apackageInfo)) {
            throw new Zy_Core_Exception(405, "操作失败, 服务不存在");
        }
        if (!in_array($apackageInfo['state'],  Service_Data_Aporderpackage::APORDER_STATUS_ABLE_MAP)) {
            throw new Zy_Core_Exception(405, "操作失败, 服务不在有效状态内, 不可进行解绑");
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

        // check order
        $serviceData = new Service_Data_Order();
        $orderLists = $serviceData->getAporderByPackageId($apackageId);
        if (empty($orderLists)) {
            throw new Zy_Core_Exception(405, "操作失败, 该服务未配置订单, 经检查");
        }
        $orderIds = Zy_Helper_Utils::arrayInt($orderLists, "order_id");

        $serviceData = new Service_Data_Curriculum();
        $ret = $serviceData->unbindByOrderIds($orderIds);
        if (!$ret) {
            throw new Zy_Core_Exception(405, "解绑失败, 请重试");
        }
        
        return array();
    }
}