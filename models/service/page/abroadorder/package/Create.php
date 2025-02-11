<?php

class Service_Page_Abroadorder_Package_Create extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $studentUid     = empty($this->request['student_uid']) ? 0 : intval($this->request['student_uid']);
        $abroadplanId   = empty($this->request['abroadplan_id']) ? 0 : intval($this->request['abroadplan_id']);
        $scheduleNums   = empty($this->request['schedule_nums']) ? 0 : floatval($this->request['schedule_nums']);
        $originBalance  = empty($this->request['origin_balance']) ? 0 : intval(floatval($this->request['origin_balance']) * 100);
        $realBalance    = empty($this->request['real_balance']) ? 0 : intval(floatval($this->request['real_balance']) * 100);
        $discountZ      = empty($this->request['discount_z']) ? 0 : intval($this->request['discount_z'] * 10);
        $discountJ      = empty($this->request['discount_j']) ? 0 : intval($this->request['discount_j'] * 100);
        $remark         = empty($this->request['remark']) ? "" : trim($this->request['remark']);

        if ($studentUid <= 0 || $abroadplanId <= 0 || $scheduleNums <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 学员, 计划, 总课时数为必填项, 不能为空");
        }

        if (mb_strlen($remark) > 100) {
            throw new Zy_Core_Exception(405, "操作失败, 备注限定100字內");
        }

        if ($discountJ < 0) {
            throw new Zy_Core_Exception(405, "操作失败, 减免优惠配置错误, 需要配置大于0的具体价格");
        }

        if ($discountZ < 0 || $discountZ >= 100) {
            throw new Zy_Core_Exception(405, "操作失败, 折扣优惠配置错误, 折扣必须在 x>0 并且 x<10 之间, 小数点后一位");
        }
        
        if ($realBalance < 0) {
            throw new Zy_Core_Exception(405, "操作失败, 实际缴费价格不能小于0");
        }

        $serviceProfile = new Service_Data_Profile();
        $studentInfo = $serviceProfile->getUserInfoByUid($studentUid);
        if (empty($studentInfo) || $studentInfo['state'] == Service_Data_Profile::STUDENT_DISABLE) {
            throw new Zy_Core_Exception(405, "操作失败, 学员不存在或已下线");
        }
        if (!$this->isOperator(Service_Data_Roles::ROLE_MODE_STUDENT_AMOUNT_HANDLE, $studentInfo['sop_uid'])) {
            throw new Zy_Core_Exception(405, "操作失败, 非学管或特定权限人员, 无权限操作");
        }
        // @2025.2.9按要求, 计划扣款必须要求账户余额充足
        if ($studentInfo["balance"] < $realBalance) {
            throw new Zy_Core_Exception(405, "操作失败, 账户余额不足, 无法创建计划服务, 请提前充值");
        }

        $serviceAbroadplan = new Service_Data_Abroadplan();
        $abroadplanInfo = $serviceAbroadplan->getAbroadplanById($abroadplanId);
        if (empty($abroadplanInfo)) {
            throw new Zy_Core_Exception(405, "操作失败, 留学&升学服务计划信息不存在");
        }

        if ($originBalance != $abroadplanInfo['price']) {
            throw new Zy_Core_Exception(405, "操作失败, 创建参数的计划原价与实际计划原价不同, 可能计划有变动, 请重新创建");
        }

        if ($scheduleNums != $abroadplanInfo['duration']) {
            throw new Zy_Core_Exception(405, "操作失败, 创建参数的计划课时与实际计划课时不同, 可能计划有变动, 请重新创建");
        }

        // check confirm conf
        $serviceConfirm = new Service_Data_Abroadplanconfirm();
        $confirm = $serviceConfirm->getConfirmById($abroadplanId);

        $profile = [
            "uid"               => $studentUid, 
            "abroadplan_id"     => $abroadplanId, 
            "schedule_nums"     => sprintf("%.2f", $scheduleNums),
            "state"             => Service_Data_Aporderpackage::APORDER_STATUS_ABLE_PEND,
            "price"             => $realBalance,
            "remark"            => $remark,
            "operator"          => OPERATOR,
            'update_time'       => time(),
            'create_time'       => time(),
            "ext"               => json_encode(array(
                "origin_balance"    => $originBalance, 
                "discount_z"        => $discountZ, 
                "discount_j"        => $discountJ,
            )),

            // 额外内容, 需要处理方法中先捞出来
            "confirm" => $confirm,
        ];

        $serviceData = new Service_Data_Aporderpackage();
        $ret = $serviceData->create($profile);
        if ($ret == false) {
            throw new Zy_Core_Exception(405, "创建失败, 请重试");
        }
        return array();
    }
}