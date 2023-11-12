<?php

class Service_Page_Student_Refund extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin() || !$this->isModeAble(Service_Data_Roles::ROLE_MODE_STUDENT_REFUND)) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $uid       = empty($this->request['uid']) ? 0 : intval($this->request['uid']);
        $reBalance = empty($this->request['recharge_balance']) ? 0 : intval($this->request['recharge_balance'] * 100);

        if ($uid <= 0 || $reBalance <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 退款金额必须大于0元");
        }

        $serviceData = new Service_Data_Profile();
        $userInfo = $serviceData->getUserInfoByUid($uid);
        if (empty($userInfo)) {
            throw new Zy_Core_Exception(405, "操作失败, 学员信息不存在");
        }

        if (empty($userInfo['balance']) || $userInfo['balance'] < $reBalance) {
            throw new Zy_Core_Exception(405, "操作失败, 账户存额不足");
        }

        $ret = $serviceData->refundUser($uid, $reBalance);
        if ($ret == false) {
            throw new Zy_Core_Exception(405, "退款失败, 请重试");
        }
        return array();
    }
}