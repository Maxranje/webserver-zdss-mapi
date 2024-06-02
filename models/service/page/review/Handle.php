<?php

class Service_Page_Review_Handle extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin() || !$this->isModeAble(Service_Data_Roles::ROLE_MODE_REVIEW_HANDLE)) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $id         = empty($this->request['id']) ? 0 : intval($this->request['id']);
        $state      = empty($this->request['review_state']) ? 0 : intval($this->request['review_state']);
        $remark     = empty($this->request['remark']) ? "" : trim($this->request['remark']);

        if ($id <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 参数错误");
        }
        if (!in_array($state, [
            Service_Data_Review::REVIEW_SUC, 
            Service_Data_Review::REVIEW_REF]
        )) {
            throw new Zy_Core_Exception(405, "操作失败, 操作选项错误");
        }
        if (mb_strlen($remark) > 100) {
            throw new Zy_Core_Exception(405, "操作失败, 备注信息太多, 限定100字内");
        }

        // 查询工单信息
        $serviceReview = new Service_Data_Review();
        $review = $serviceReview->getReviewById($id);
        if (empty($review) || $review["state"] != Service_Data_Review::REVIEW_ING) {
            throw new Zy_Core_Exception(405, "操作失败, 工单不存在或已处理, 请刷新工单列表重试");
        }

        // 查询工单关联信息
        $workID = intval($review['work_id']);
        if ($review['type'] == Service_Data_Profile::RECHARGE || 
            $review['type'] == Service_Data_Profile::REFUND) {  // 充值或退款

            // 查询账户数据
            $serviceData = new Service_Data_Capital();
            $capital = $serviceData->getCapitalById($workID);
            if (empty($capital)) {
                throw new Zy_Core_Exception(405, "操作失败, 工单关联业务不存在");
            }

            // 查询用户信息
            $serviceProfile = new Service_Data_Profile();
            $userInfo = $serviceProfile->getUserInfoByUid(intval($review['uid']));
            if (empty($userInfo)) {
                throw new Zy_Core_Exception(405, "操作失败, 工单关联用户异常或已被删除, 请检查");
            }

            // 更新db
            if ($review['type'] == Service_Data_Review::REVIEW_TYPE_RECHARGE) {
                $ret = $serviceReview->rechargeHandle($id, $userInfo, $capital, $remark, $state);
            } else {
                $ret = $serviceReview->refundHandle($id, $userInfo, $capital, $remark, $state);
            }
            if (!$ret) {
                throw new Zy_Core_Exception(405, "审核失败, 请重试");
            }

        } else {
            throw new Zy_Core_Exception(405, "操作失败, 工单类型错误, 联系系统管理员");
        }

        return array();        
    }
}