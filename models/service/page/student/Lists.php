<?php

class Service_Page_Student_Lists extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $pn         = empty($this->request['page']) ? 1 : intval($this->request['page']);
        $rn         = empty($this->request['perPage']) ? 20 : intval($this->request['perPage']);
        $bpid       = empty($this->request['bpid']) ? 0 : intval($this->request['bpid']);
        $name       = empty($this->request['name']) ? "" : strval($this->request['name']);
        $phone      = empty($this->request['phone']) ? "" : strval($this->request['phone']);
        $state      = empty($this->request['state']) ? 0 : intval($this->request['state']);
        $nickname   = empty($this->request['nickname']) ? "" : strval($this->request['nickname']);
        $isSelect   = empty($this->request['is_select']) ? false : true;
        $isDefer    = empty($this->request['is_defer']) ? false : true;

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
        $orderInfos = $serviceData->getOrderCountByStudentUids($studentUids);
        $orderInfos = array_column($orderInfos, null, 'student_uid');

        // 获取待结算
        // $serviceData = new Service_Data_Curriculum();
        // $scheduleCount = $serviceData->getScheduleTimeCountByStudentUid($studentUids);

        $isModeRecharge = $this->isModeAble(Service_Data_Roles::ROLE_MODE_STUDENT_RECHARGE);
        $isModeRefund = $this->isModeAble(Service_Data_Roles::ROLE_MODE_STUDENT_REFUND);
        $isPartner = $this->checkPartner();

        $result = array();
        foreach ($lists as $item) {
            $ext = empty($item["ext"])? array() : json_decode($item['ext'], true);
            $item['is_partner']  = $isPartner ? 1 : 0;
            $item["remark"]      = empty($ext['remark']) ? "" : $ext['remark'];
            $item['order_count'] = empty($orderInfos[$item['uid']]['order_count']) ? "-" : $orderInfos[$item['uid']]['order_count'];
            $item['balance_info']= sprintf("%.2f", $item['balance'] / 100);
            $item['total_balance']= empty($ext['total_balance']) ? "0.00" : sprintf("%.2f", $ext['total_balance'] / 100);
            //$item['uncheck_schedule_nums'] = empty($scheduleCount[$item['uid']]) ? "-" : sprintf("%.2f小时", $scheduleCount[$item['uid']]);
            $item['birthplace']  = empty($birthplaces[$item['bpid']]['name']) ? "" : $birthplaces[$item['bpid']]['name'];
            $item['is_re']       = $isModeRecharge ? 1 : 0;
            $item['is_rd']       = $isModeRefund ? 1 : 0;
            $item['sop_name']    = empty($sopInfos[$item['sop_uid']]['nickname']) ? "" : $sopInfos[$item['sop_uid']]['nickname'];
            $item['create_time'] = date("Y年m月d日", $item['create_time']);
            $item['update_time'] = date("Y年m月d日", $item['update_time']);
            unset($item['passport']);
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