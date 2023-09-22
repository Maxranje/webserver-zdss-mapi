<?php

class Service_Page_Order_Create extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $studentUid     = empty($this->request['student_uid']) ? 0 : intval($this->request['student_uid']);
        $subjectId      = empty($this->request['subject_id']) ? 0 : intval($this->request['subject_id']);
        $scheduleNums   = empty($this->request['schedule_nums']) ? 0 : floatval($this->request['schedule_nums']);
        $originPrice    = empty($this->request['origin_price']) ? 0 : intval(floatval($this->request['origin_price']) * 100);
        $originBalance  = empty($this->request['origin_balance']) ? 0 : intval(floatval($this->request['origin_balance']) * 100);
        $realBalance    = empty($this->request['real_balance']) ? 0 : intval(floatval($this->request['real_balance']) * 100);
        $realPrice      = empty($this->request['real_price']) ? 0 : intval(floatval($this->request['real_price']) * 100);
        $discountZ      = empty($this->request['discount_z']) ? 0 : intval($this->request['discount_z'] * 10);
        $discountJ      = empty($this->request['discount_j']) ? 0 : intval($this->request['discount_j'] * 100);
        $remark         = empty($this->request['remark']) ? "" : trim($this->request['remark']);

        if ($studentUid <= 0 || $subjectId <= 0 || $scheduleNums <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 学员, 科目, 总课时数为必填项, 不能为空");
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

        $serviceProfile = new Service_Data_Profile();
        $studentInfo = $serviceProfile->getUserInfoByUid($studentUid);
        if (empty($studentInfo) || $studentInfo['state'] != Service_Data_Profile::STUDENT_ABLE) {
            throw new Zy_Core_Exception(405, "操作失败, 学员不存在或已下线");
        }

        $serviceSubject = new Service_Data_Subject();
        $subjectInfo = $serviceSubject->getSubjectById($subjectId);
        if (empty($subjectInfo)) {
            throw new Zy_Core_Exception(405, "操作失败, 科目不存在");
        }

        if ($originPrice != $subjectInfo['price']) {
            throw new Zy_Core_Exception(405, "操作失败, 参数中科目价格和实时获取科目价格不同, 请重试");
        }

        $profile = [
            "subject_id"        => $subjectId, 
            "student_uid"       => $studentUid, 
            "balance"           => $realBalance, 
            "price"             => $realPrice,
            "discount_z"        => $discountZ, 
            "discount_j"        => $discountJ,
            "transfer_id"       => 0,
            "operator"          => OPERATOR,
            'update_time'       => time(),
            'create_time'       => time(),
            "ext"               => json_encode(array(
                "origin_balance"    => $originBalance, 
                "origin_price"      => $originPrice,
                "real_balance"      => $realBalance, 
                "real_price"        => $realPrice,
                "schedule_nums"     => $scheduleNums,
                "transfer_balance"  => 0,
                "refund_balance"    => 0,
                "remark"            => $remark,
            )),
        ];

        $serviceOrder = new Service_Data_Order();
        $ret = $serviceOrder->create($profile);
        if ($ret == false) {
            throw new Zy_Core_Exception(405, "创建失败, 请重试");
        }
        return array();
    }
}