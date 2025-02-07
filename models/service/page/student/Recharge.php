<?php

class Service_Page_Student_Recharge extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin() || !$this->isModeAble(Service_Data_Roles::ROLE_MODE_STUDENT_RECHARGE)) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $uid            = empty($this->request['uid']) ? 0 : intval($this->request['uid']);
        $reBalance      = empty($this->request['recharge_balance']) ? 0 : intval($this->request['recharge_balance'] * 100);
        $remark         = empty($this->request['remark']) ? "" : trim($this->request['remark']);
        $partner        = empty($this->request['partner_uid']) ? 0 : intval($this->request['partner_uid']);
        $abroadplanId   = empty($this->request['abroadplan_id']) ? "" : trim($this->request['abroadplan_id']);

        if ($uid <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 用户参数错误");
        }

        $abroadplanInfo = array();
        if (!empty($abroadplanId)) {
            list($abroadplanId, $price) = explode("-", $abroadplanId);
            $serviceData = new Service_Data_Abroadplan();
            $abroadplanInfo = $serviceData->getAbroadplanById(intval($abroadplanId));
            if (empty($abroadplanInfo)) {
                throw new Zy_Core_Exception(405, "操作失败, 留学&升学服务计划不存在");
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

        if ($partner > 0) {
            $partnerInfo = $serviceData->getUserInfoByUid($partner);
            if (empty($partnerInfo)) {
                throw new Zy_Core_Exception(405, "操作失败, 协作人员信息不存在");
            }
        }

        $ret = $serviceData->rechargeUser($userInfo, $reBalance, $abroadplanInfo, $remark, $partner);
        if ($ret == false) {
            throw new Zy_Core_Exception(405, "充值失败, 请重试");
        }
        return array();
    }
}