<?php

class Service_Page_Student_Recharge extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin() || !$this->isModeAble(Service_Data_Roles::ROLE_MODE_STUDENT_RECHARGE)) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $uid       = empty($this->request['uid']) ? 0 : intval($this->request['uid']);
        $reBalance = empty($this->request['recharge_balance']) ? 0 : intval($this->request['recharge_balance'] * 100);

        if ($uid <= 0 || $reBalance <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 充值金额必须大于0元");
        }

        $serviceData = new Service_Data_Profile();
        $userInfo = $serviceData->getUserInfoByUid($uid);
        if (empty($userInfo)) {
            throw new Zy_Core_Exception(405, "操作失败, 学员信息不存在");
        }

        $ret = $serviceData->rechargeUser($uid, $reBalance);
        if ($ret == false) {
            throw new Zy_Core_Exception(405, "充值失败, 请重试");
        }
        return array();
    }
}