<?php

class Service_Page_Abroadorder_Create extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $apackageId     = empty($this->request['apackage_id']) ? 0 : intval($this->request['apackage_id']);
        $claszemapId    = empty($this->request['claszemap_id']) ? 0 : intval($this->request['claszemap_id']);
        $scheduleNums   = empty($this->request['order_duration']) ? 0 : floatval($this->request['order_duration']);

        // check params
        if ($apackageId <= 0 || $claszemapId <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 学员, 科目为必填项, 不能为空");
        }
        if ($scheduleNums <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 课时数为必填项, 必须大于0时");
        }

        // check apackage
        $serviceData = new Service_Data_Aporderpackage();
        $apackageInfo = $serviceData->getAbroadpackageById($apackageId);
        if (empty($apackageInfo)) {
            throw new Zy_Core_Exception(405, "操作失败, 服务不存在");
        }
        if (!in_array($apackageInfo['state'], Service_Data_Aporderpackage::APORDER_STATUS_ABLE_MAP)) {
            throw new Zy_Core_Exception(405, "操作失败, 服务不在有效状态内, 不可创建订单");
        }

        // check abroadplan
        $serviceData = new Service_Data_Abroadplan();
        $abroadplanInfo = $serviceData->getAbroadplanById(intval($apackageInfo['abroadplan_id']));
        if (empty($abroadplanInfo)) {
            throw new Zy_Core_Exception(405, "操作失败, 已绑定的留学计划不存在或被删除, 请联系管理员");
        }

        // check userinfo
        $serviceProfile = new Service_Data_Profile();
        $studentInfo = $serviceProfile->getUserInfoByUid(intval($apackageInfo['uid']));
        if (empty($studentInfo) || $studentInfo['state'] == Service_Data_Profile::STUDENT_DISABLE) {
            throw new Zy_Core_Exception(405, "操作失败, 学员不存在或已下线");
        }
        if (!$this->isOperator(Service_Data_Roles::ROLE_MODE_STUDENT_AMOUNT_HANDLE, $studentInfo['sop_uid'])) {
            throw new Zy_Core_Exception(405, "操作失败, 非学管或特定权限人员, 无权限操作");
        }

        // check claszemap
        $serviceClasze = new Service_Data_Claszemap();
        $claszemap = $serviceClasze->getClaszemapById($claszemapId);
        if (empty($claszemap)) {
            throw new Zy_Core_Exception(405, "操作失败, 科目&班型信息不存在");
        }

        // check subject
        $serviceSubject = new Service_Data_Subject();
        $subjectInfo = $serviceSubject->getSubjectById(intval($claszemap['subject_id']));
        if (empty($subjectInfo)) {
            throw new Zy_Core_Exception(405, "操作失败, 科目不存在");
        }

        // check birthplace
        $serviceBirthplace = new Service_Data_Birthplace();
        $birthplace = $serviceBirthplace->getBirthplaceById(intval($claszemap['bpid']));
        if (empty($birthplace)) {
            throw new Zy_Core_Exception(405, "操作失败, 学员生源地不存在");
        }

        // check clasze
        $serviceClasze = new Service_Data_Clasze();
        $clasze = $serviceClasze->getClaszeById(intval($claszemap['cid']));
        if (empty($clasze)) {
            throw new Zy_Core_Exception(405, "操作失败, 班型不存在");
        }

        // check duration
        $serviceOrder = new Service_Data_Order();
        $aporders = $serviceOrder->getAporderDurationByApackageIds(array($apackageId));
        if ($aporders[$apackageId] + $scheduleNums > $apackageInfo["schedule_nums"]) {
            throw new Zy_Core_Exception(405, "操作失败, 订单配置的可是超过服务已有课时");
        }

        $orderInfo = json_encode(array(
            "abroadplan_name"   => $abroadplanInfo["name"],
            "schedule_nums"     => $scheduleNums,
            "action_type"       => "create",
        ));

        $profile = [
            "type"              => Service_Data_Order::ORDER_TYPE_ABROADPLAN,
            "bpid"              => intval($claszemap['bpid']), 
            "cid"               => intval($claszemap['cid']), 
            "subject_id"        => intval($claszemap['subject_id']), 
            "student_uid"       => intval($studentInfo['uid']), 
            "apackage_id"       => $apackageId,
            "abroadplan_id"     => intval($abroadplanInfo['id']),
            "balance"           => 0,
            "price"             => 0,
            "discount_z"        => 0,
            "discount_j"        => 0,
            "isfree"            => 0,
            "operator"          => OPERATOR,
            "order_info"        => $orderInfo,
            'update_time'       => time(),
            'create_time'       => time(),
            "ext"               => json_encode(array(
                "schedule_nums"     => $scheduleNums,
            )),
        ];

        $ret = $serviceOrder->createAporder($profile);
        if ($ret == false) {
            throw new Zy_Core_Exception(405, "创建失败, 请重试");
        }
        return array();
    }
}