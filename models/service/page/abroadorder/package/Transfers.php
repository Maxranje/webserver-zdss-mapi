<?php

class Service_Page_Abroadorder_Package_Transfers extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }
        $apackageId     = empty($this->request['apackage_id']) ? 0 : intval($this->request['apackage_id']);
        $isNew          = empty($this->request['is_new']) ? false : true;
        $isNoPrice      = empty($this->request['is_noprice']) ? false : true;

        if ($apackageId <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 学员信息或原服务信息获取失败");
        }

        if ($isNew) {
            return $this->transferNewApackage($isNoPrice) ;
        } else {
            return $this->transferHasApackage() ;
        }
    }

    private function transferNewApackage($isNoPrice = false) {
        $apackageId     = empty($this->request['apackage_id']) ? 0 : intval($this->request['apackage_id']);
        $abroadplanId   = empty($this->request['transfer_abroadplan_id']) ? 0 : intval($this->request['transfer_abroadplan_id']);
        $scheduleNums   = empty($this->request['schedule_nums']) ? 0 : floatval($this->request['schedule_nums']);
        $originBalance  = empty($this->request['origin_balance']) ? 0 : intval(floatval($this->request['origin_balance']) * 100);
        $realBalance    = empty($this->request['real_balance']) ? 0 : intval(floatval($this->request['real_balance']) * 100);
        $discountZ      = empty($this->request['transfer_discount_z']) ? 0 : intval($this->request['transfer_discount_z'] * 10);
        $discountJ      = empty($this->request['transfer_discount_j']) ? 0 : intval($this->request['transfer_discount_j'] * 100);
        $remark         = empty($this->request['transfer_remark']) ? "" : trim($this->request['transfer_remark']);

        if ($abroadplanId <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 新计划为必填项, 不能为空");
        }

        if (mb_strlen($remark) > 100) {
            throw new Zy_Core_Exception(405, "操作失败, 备注限定100字內");
        }

        if (!$isNoPrice) {
            if ($discountJ < 0) {
                throw new Zy_Core_Exception(405, "操作失败, 减免优惠配置错误, 需要配置大于0的具体价格");
            }
    
            if ($discountZ < 0 || $discountZ >= 100) {
                throw new Zy_Core_Exception(405, "操作失败, 折扣优惠配置错误, 折扣必须在 x>0 并且 x<10 之间, 小数点后一位");
            }
    
            if ($realBalance < 0) {
                throw new Zy_Core_Exception(405, "操作失败, 实际缴费价格不能小于0");
            }    
        }

        // check origin apackage
        $serviceApackage = new Service_Data_Aporderpackage();
        $originApackage = $serviceApackage->getAbroadpackageById($apackageId);
        if (empty($originApackage) || $originApackage["state"] != Service_Data_Aporderpackage::APORDER_STATUS_ABLE) {
            throw new Zy_Core_Exception(405, "操作失败, 原服务不存在或无效无法结转, 请检查");
        }
        
        $studentUid = intval($originApackage["uid"]);
        
        $serviceProfile = new Service_Data_Profile();
        $studentInfo = $serviceProfile->getUserInfoByUid($studentUid);
        if (empty($studentInfo) || $studentInfo['state'] == Service_Data_Profile::STUDENT_DISABLE) {
            throw new Zy_Core_Exception(405, "操作失败, 学员不存在或已下线");
        }        
        if (!$this->isOperator(Service_Data_Roles::ROLE_MODE_STUDENT_AMOUNT_HANDLE, $studentInfo['sop_uid'])) {
            throw new Zy_Core_Exception(405, "操作失败, 非学管或特定权限人员, 无权限操作");
        }

        $serviceAbroadplan = new Service_Data_Abroadplan();
        $abroadplanInfo = $serviceAbroadplan->getAbroadplanById($abroadplanId);
        if (empty($abroadplanInfo)) {
            throw new Zy_Core_Exception(405, "操作失败, 留学&升学服务计划信息不存在");
        }

        if (!$isNoPrice) {
            if ($originBalance != $abroadplanInfo['price']) {
                throw new Zy_Core_Exception(405, "操作失败, 创建参数的计划原价与实际计划原价不同, 可能计划有变动, 请重新创建");
            }

            if ($scheduleNums != $abroadplanInfo['duration']) {
                throw new Zy_Core_Exception(405, "操作失败, 创建参数的计划课时与实际计划课时不同, 可能计划有变动, 请重新创建");
            }
            if ($studentInfo["balance"] < $realBalance) {
                throw new Zy_Core_Exception(405, "操作失败, 需扣费场景下, 账户余额不足, 无法结转到新计划服务, 请提前充值");
            }
        } else {
            $realBalance = 0;
            $originBalance = $abroadplanInfo['price'];
            $discountZ = $discountJ = 0;
        }

        // check confirm conf
        $serviceConfirm = new Service_Data_Abroadplanconfirm();
        $confirm = $serviceConfirm->getConfirmById($abroadplanId);    

        // get origin duration
        $serviceData = new Service_Data_Order();
        $orderIds = $serviceData->getAporderByPackageId($apackageId);
        $orderIds = Zy_Helper_Utils::arrayInt($orderIds, "order_id");
        
        $originA = $originC = $originU = 0;
        if (!empty($orderIds)) {
            $serviceData = new Service_Data_Curriculum();
            $orderCount = $serviceData->getScheduleTimeCountByOrder($orderIds);
            foreach ($orderCount as $orderId => $v) {
                $originA+=empty($v['a']) ? 0 : intval($v['a']);
                $originC+=empty($v['c']) ? 0 : intval($v['c']);
                $originU+=empty($v['u']) ? 0 : intval($v['u']);
            }
        }

        // no band, check apackageid has band no check resource
        if ($originU > 0) {
            throw new Zy_Core_Exception(405, sprintf("操作失败, 原服务中还有 %s 小时的排课绑定未结算, 需先解绑在结转, 或请选择系统的一键解绑能力", floatval(sprintf("%.2f", $originU))));
        }

        $profile = [
            "uid"               => $studentUid, 
            "abroadplan_id"     => $abroadplanId, 
            "schedule_nums"     => 0,
            "state"             => Service_Data_Aporderpackage::APORDER_STATUS_TRANS_PEND,
            "price"             => $realBalance,
            "remark"            => $remark,
            "operator"          => OPERATOR,
            'update_time'       => time(),
            'create_time'       => time(),
            "ext"               => json_encode(array(
                "origin_balance"    => $originBalance, 
                "discount_z"        => $discountZ, 
                "discount_j"        => $discountJ,
                "from_transfer_id"  => $apackageId,
            )),
            // 额外内容, 需要处理方法中先捞出来
            "confirm" => $confirm,
            // origin apackageid
            "origin_apackage" => $originApackage,
            // origin duration
            "transfer_schedule_nums" => $originApackage['schedule_nums'] - $originC,
        ];

        $ret = $serviceApackage->transferNew($profile);
        if ($ret == false) {
            throw new Zy_Core_Exception(405, "结转失败, 请重试");
        }
        return array();
    }

    // 已有的
    private function transferHasApackage() {
        $apackageId     = empty($this->request['apackage_id']) ? 0 : intval($this->request['apackage_id']);
        $transferId     = empty($this->request['transfer_apackage_id']) ? 0 : intval($this->request['transfer_apackage_id']);

        if ($apackageId <= 0 || $transferId <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 参数错误");
        }

        // check origin apackage
        $serviceApackage = new Service_Data_Aporderpackage();
        $apackageInfos = $serviceApackage->getAbroadpackageByIds(array($apackageId, $transferId));
        $apackageInfos = array_column($apackageInfos, null, "id");
        $originApackage = !empty($apackageInfos[$apackageId]) ? $apackageInfos[$apackageId] : array();
        $distinApackage = !empty($apackageInfos[$transferId]) ? $apackageInfos[$transferId] : array();
        if (empty($originApackage) || $originApackage["state"] != Service_Data_Aporderpackage::APORDER_STATUS_ABLE) {
            throw new Zy_Core_Exception(405, "操作失败, 原服务不存在或无效无法结转, 请检查");
        }
        if (empty($distinApackage) || $distinApackage["state"] != Service_Data_Aporderpackage::APORDER_STATUS_ABLE) {
            throw new Zy_Core_Exception(405, "操作失败, 目标服务不存在或无效状态, 无法结转, 请检查");
        }    
        
        $serviceProfile = new Service_Data_Profile();
        $studentInfo = $serviceProfile->getUserInfoByUid(intval($originApackage["uid"]));
        if (empty($studentInfo) || $studentInfo['state'] == Service_Data_Profile::STUDENT_DISABLE) {
            throw new Zy_Core_Exception(405, "操作失败, 学员不存在或已下线");
        }
        if (!$this->isOperator(Service_Data_Roles::ROLE_MODE_STUDENT_AMOUNT_HANDLE, $studentInfo['sop_uid'])) {
            throw new Zy_Core_Exception(405, "操作失败, 非学管或特定权限人员, 无权限操作");
        }

        // get origin duration
        $serviceData = new Service_Data_Order();
        $orderIds = $serviceData->getAporderByPackageId($apackageId);
        $orderIds = Zy_Helper_Utils::arrayInt($orderIds, "order_id");
        
        $originA = $originC = $originU = 0;
        if (!empty($orderIds)) {
            $serviceData = new Service_Data_Curriculum();
            $orderCount = $serviceData->getScheduleTimeCountByOrder($orderIds);
            foreach ($orderCount as $orderId => $v) {
                $originA+=empty($v['a']) ? 0 : intval($v['a']);
                $originC+=empty($v['c']) ? 0 : intval($v['c']);
                $originU+=empty($v['u']) ? 0 : intval($v['u']);
            }
        }

        // no band, check apackageid has band no check resource
        if ($originU > 0) {
            throw new Zy_Core_Exception(405, sprintf("操作失败, 原服务中还有 %s 小时的排课绑定未结算, 需先解绑在结转, 或请选择系统的一键解绑能力", floatval(sprintf("%.2f", $originU))));
        }

        $profile = [
            "origin_apackage" => $originApackage,
            "distin_apackage" => $distinApackage,
            "transfer_schedule_nums" => $originApackage['schedule_nums'] - $originC,
        ];

        $ret = $serviceApackage->transferHas($profile);
        if ($ret == false) {
            throw new Zy_Core_Exception(405, "结转失败, 请重试");
        }
        return array();
    }    
}