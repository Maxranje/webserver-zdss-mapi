<?php

class Service_Page_Student_Lists extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $pn             = empty($this->request['page']) ? 1 : intval($this->request['page']);
        $rn             = empty($this->request['perPage']) ? 20 : intval($this->request['perPage']);
        $bpid           = empty($this->request['bpid']) ? 0 : intval($this->request['bpid']);
        $name           = empty($this->request['name']) ? "" : strval($this->request['name']);
        $phone          = empty($this->request['phone']) ? "" : strval($this->request['phone']);
        $state          = empty($this->request['state']) ? 0 : intval($this->request['state']);
        $sopuid         = empty($this->request['sop_uid']) ? 0 : intval($this->request['sop_uid']);
        $balanceState   = empty($this->request['balance_state']) ? 0 : intval($this->request['balance_state']);
        $nickname       = empty($this->request['nickname']) ? "" : strval($this->request['nickname']);
        $isSelect       = empty($this->request['is_select']) ? false : true;
        $isDefer        = empty($this->request['is_defer']) ? false : true;

        $pn = ($pn-1) * $rn;

        $conds = array(
            'type' => Service_Data_Profile::USER_TYPE_STUDENT,
        );

        if ($this->checkPartner()) {
            $bpid = $this->getPartnerBpid($this->adption['userid']);
        }

        if (!empty($nickname)) {
            $conds[] = "nickname like '%".$nickname."%'";
        }

        if (!empty($state)) {
            $conds[] = sprintf("state = '%d'", $state);
        }

        if (!empty($name)) {
            $conds[] = sprintf("name = '%s'", $name);
        }

        if (!empty($phone)) {
            $conds[] = sprintf("phone = '%s'", $phone);
        }

        if ($bpid > 0) {
            $conds[] = sprintf("bpid = %d", $bpid);
        }

        if ($sopuid > 0) {
            $conds[] = sprintf("sop_uid = %d", $sopuid);
        }

        if ($balanceState > 0) {
            $conds[] = $balanceState == 2 ? "balance < 0" : "balance >= 0";
        }
        
        $serviceData = new Service_Data_Profile();

        $arrAppends = array(
            'order by uid desc',
        );
        if (!$isSelect) {
            $arrAppends[] = "limit {$pn} , {$rn}";
        }

        $lists = $serviceData->getListByConds($conds, array(), NULL, $arrAppends);
        if (empty($lists)) {
            return array();
        }

        if ($isSelect) {
            return $this->formatSelect($lists, $isDefer);
        } 

        $total = $serviceData->getTotalByConds($conds);
        return array(
            'rows' => $this->formatDefault($lists),
            'total' => $total,
        );
    }

    // 格式化数据
    private function formatDefault($lists) {
        if (empty($lists)) {
            return array();
        }

        $studentUids = Zy_Helper_Utils::arrayInt($lists, "uid");
        $bpids = Zy_Helper_Utils::arrayInt($lists, "bpid");
        $sopuids = Zy_Helper_Utils::arrayInt($lists, "sop_uid");

        // 获取管理员
        $serviceData = new Service_Data_Profile();
        $sopInfos = $serviceData->getUserInfoByUids($sopuids);
        $sopInfos = array_column($sopInfos, null, "uid");

        // 获取生源地
        $serviceData = new Service_Data_Birthplace();
        $birthplaces = $serviceData->getBirthplaceByIds($bpids);
        $birthplaces = array_column($birthplaces, null, "id");

        // 获取订单量
        $serviceData = new Service_Data_Order();
        $orderCount = $serviceData->getNmorderTotalBySuids($studentUids);

        // 获取留学服务数
        $serviceData = new Service_Data_Aporderpackage();
        $apackageCounts = $serviceData->getApackageCountByUids($studentUids);
        
        //获取充值状态
        $serviceData = new Service_Data_Review();
        $reviewInfos = $serviceData->getR2ReviewingByUid($studentUids);

        // get role
        $isModeRecharge     = $this->isModeAble(Service_Data_Roles::ROLE_MODE_STUDENT_RECHARGE);
        $isModeRefund       = $this->isModeAble(Service_Data_Roles::ROLE_MODE_STUDENT_REFUND);
        $isPartner          = $this->checkPartner();

        $result = array();
        foreach ($lists as $item) {
            $ext = empty($item["ext"])? array() : json_decode($item['ext'], true);
            $item['is_partner']         = $isPartner ? 1 : 0;
            $item["remark"]             = empty($ext['remark']) ? "" : $ext['remark'];
            $item['order_count']        = $orderCount[$item['uid']]['count'];
            $item['order_balance']      = sprintf("%.2f", $orderCount[$item['uid']]['balance'] / 100);
            $item['balance_f']          = sprintf("%.2f", $item['balance'] / 100);
            $item['total_balance']      = empty($ext['total_balance']) ? "0.00" : sprintf("%.2f", $ext['total_balance'] / 100);
            $item['birthplace']         = empty($birthplaces[$item['bpid']]['name']) ? "" : $birthplaces[$item['bpid']]['name'];
            $item['sop_name']           = empty($sopInfos[$item['sop_uid']]['nickname']) ? "" : $sopInfos[$item['sop_uid']]['nickname'];
            $item['create_time']        = date("Y年m月d日", $item['create_time']);
            $item['update_time']        = date("Y年m月d日", $item['update_time']);
            $item['is_re']              = $isModeRecharge ? 1 : 0;
            $item['is_rd']              = $isModeRefund ? 1 : 0;
            $item["is_edit"]            = $this->isOperator(Service_Data_Roles::ROLE_MODE_STUDENT_EDIT, $item["sop_uid"]) ? 1 : 0;
            $item['apackage_count']     = $apackageCounts[$item['uid']] ;
            $item["review_state"]       = empty($reviewInfos[$item["uid"]]['type']) ? 0 : $reviewInfos[$item["uid"]]['type'];
            unset($item['passport']);
            
            if (!$this->isOperator(Service_Data_Roles::ROLE_MODE_STUDENT_AMOUNT_HANDLE, $item["sop_uid"]) && !$this->checkPartner()){
                $item["order_balance"]      = "***";
                $item["balance"]            = "***";
                $item["balance_f"]          = "***";
                $item["total_balance"]      = "***";
                unset($item["ext"]);
            }
            $result[] = $item;
        }
        return $result;
    }

    // Select格式化数据
    private function formatSelect($lists, $isDefer = false) {
        if (empty($lists)) {
            return array();
        }

        $options = array();
        foreach ($lists as $item) {
            $tmp = array(
                'label' => $item['nickname'],
                'value' => $item['uid'],
            );
            if ($isDefer) {
                $tmp['defer'] = true;
                $tmp['children'] = [];
            }
            $options[] = $tmp;
        }
        return array('options' => array_values($options));
    }
}