<?php

class Service_Page_Student_Recharge extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin() || !$this->isModeAble(Service_Data_Roles::ROLE_MODE_STUDENT_RECHARGE)) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $uid       = empty($this->request['uid']) ? 0 : intval($this->request['uid']);
        $reBalance = empty($this->request['recharge_balance']) ? 0 : intval($this->request['recharge_balance'] * 100);
        $plan      = empty($this->request['plan']) ? "" : trim($this->request['plan']);
        $remark    = empty($this->request['remark']) ? "" : trim($this->request['remark']);

        if ($uid <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 用户参数错误");
        }

        $planInfo = array();
        if (!empty($plan)) {
            list($planId, $price) = explode("-", $plan);
            $serviceData = new Service_Data_Plan();
            $planInfo = $serviceData->getPlanById(intval($planId));
            if (empty($planInfo)) {
                throw new Zy_Core_Exception(405, "操作失败, 计划不存在");
            }
        }

        if ($reBalance == 0) {
            throw new Zy_Core_Exception(405, "操作失败, 充值总金额不能为0元");
        }

        if (mb_strlen($remark) > 100) {
            throw new Zy_Core_Exception(405, "操作失败, 备注信息太多, 限定100字内");
        }

        $serviceData = new Service_Data_Profile();
        $userInfo = $serviceData->getUserInfoByUid($uid);
        if (empty($userInfo)) {
            throw new Zy_Core_Exception(405, "操作失败, 学员信息不存在");
        }

        $ret = $serviceData->rechargeUser($userInfo, $reBalance, $planInfo, $remark);
        if ($ret == false) {
            throw new Zy_Core_Exception(405, "充值失败, 请重试");
        }
        return array();
    }
}