<?php

class Service_Page_Student_Refund extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin() || !$this->isModeAble(Service_Data_Roles::ROLE_MODE_STUDENT_REFUND)) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $uid       = empty($this->request['uid']) ? 0 : intval($this->request['uid']);
        $reBalance = empty($this->request['refund_balance']) ? 0 : intval($this->request['refund_balance'] * 100);
        $rbBalance = empty($this->request['refund_back_balance']) ? 0 : intval($this->request['refund_back_balance'] * 100);
        $remark    = empty($this->request['remark']) ? "" : trim($this->request['remark']);

        if ($uid <= 0 || $reBalance <= 0 || $reBalance + $rbBalance <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 实际退款金额必须大于0元");
        }

        if (mb_strlen($remark) > 100) {
            throw new Zy_Core_Exception(405, "操作失败, 备注信息太多, 限定100字内");
        }

        // check review
        $serviceData = new Service_Data_Review();
        $total = $serviceData->getTotalByConds(array(
            "uid" => $uid, 
            "state" => Service_Data_Review::REVIEW_ING,
            sprintf("type in (%s)", implode(",", [Service_Data_Review::REVIEW_TYPE_RECHARGE, Service_Data_Review::REVIEW_TYPE_REFUND]))
        ));
        if ($total > 0) {
            throw new Zy_Core_Exception(405, "操作失败, 当前学员有审批中的充值或退款记录, 需要审批完毕后才能重新提单");
        }        

        $serviceData = new Service_Data_Profile();
        $userInfo = $serviceData->getUserInfoByUid($uid);
        if (empty($userInfo)) {
            throw new Zy_Core_Exception(405, "操作失败, 学员信息不存在");
        }

        $totalBalance = $reBalance + $rbBalance;

        if (empty($userInfo['balance']) || $userInfo['balance'] < $totalBalance) {
            throw new Zy_Core_Exception(405, "操作失败, 账户存额不足");
        }

        // 账户一定会有总存额.
        $ext = empty($userInfo['ext']) ? array() : json_decode($userInfo['ext'], true);
        if (empty($ext["total_balance"])) {
            throw new Zy_Core_Exception(405, "操作失败, 账户总存额异常, 请联系管理员");
        }

        $ret = $serviceData->refundUser($userInfo, $reBalance, $rbBalance, $remark);
        if ($ret == false) {
            throw new Zy_Core_Exception(405, "退款失败, 请重试");
        }
        return array();
    }
}