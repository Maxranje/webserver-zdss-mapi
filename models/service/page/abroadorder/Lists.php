<?php

class Service_Page_Abroadorder_Lists extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $pn             = empty($this->request['page']) ? 1 : intval($this->request['page']);
        $rn             = empty($this->request['perPage']) ? 20 : intval($this->request['perPage']);
        $orderId        = empty($this->request['order_id']) ? 0 : intval($this->request['order_id']);
        $searchApackage = empty($this->request['search_apackage_id']) ? 0 : intval($this->request['search_apackage_id']);
        $apackageId     = empty($this->request['apackage_id']) ? 0 : intval($this->request['apackage_id']);
        $abroadplanId   = empty($this->request['abroadplan_id']) ? 0 : intval($this->request['abroadplan_id']);
        $studentUid     = empty($this->request['student_uid']) ? 0 : intval($this->request['student_uid']);
        $isSelect       = empty($this->request['is_select']) ? false : true;
        $pn             = ($pn-1) * $rn;

        $conds = array(
            "type" => Service_Data_Order::ORDER_TYPE_ABROADPLAN,
        );

        if ($orderId > 0) {
            $conds["order_id"] = $orderId;
        }

        if ($searchApackage > 0) {
            $conds["apackage_id"] = $searchApackage;
        }

        if ($apackageId > 0) {
            $conds["apackage_id"] = $apackageId;
        }        

        if ($abroadplanId > 0) {
            $conds["abroadplan_id"] = $abroadplanId;
        }

        if ($studentUid > 0){
            $conds["student_uid"] = $studentUid;
        }

        $arrAppends[] = 'order by order_id desc';
        if(!$isSelect) {
            $arrAppends[] = "limit {$pn} , {$rn}";
        }

        $serviceData = new Service_Data_Order();
        $lists = $serviceData->getListByConds($conds, array(), NULL, $arrAppends);
        if (empty($lists)) {
            return array();
        }
        
        $lists = $this->formatDefault($lists, $isSelect);
        if ($isSelect) {
            return $this->formatSelect($lists);
        }
        
        $total = $serviceData->getTotalByConds($conds);
        return array(
            'rows' => $lists,
            'total' => $total,
        );
    }

    // 默认格式化
    private function formatDefault ($lists, $isSelect) {
        $abroadplanIds  = Zy_Helper_Utils::arrayInt($lists, 'abroadplan_id');
        $studentUids    = Zy_Helper_Utils::arrayInt($lists, 'student_uid');
        $subjectIds     = Zy_Helper_Utils::arrayInt($lists, 'subject_id');
        $orderIds       = Zy_Helper_Utils::arrayInt($lists, 'order_id');
        $operatorIds    = Zy_Helper_Utils::arrayInt($lists, 'operator');
        $bpdis          = Zy_Helper_Utils::arrayInt($lists, 'bpid');
        $cids           = Zy_Helper_Utils::arrayInt($lists, 'cid');
        $apackageIds    = Zy_Helper_Utils::arrayInt($lists, 'apackage_id');

        $uids = array_unique(array_merge($studentUids, $operatorIds));

        $serviceData = new Service_Data_Subject();
        $subjectInfos = $serviceData->getListByConds(array(sprintf("id in (%s)", implode(",", $subjectIds))));
        $subjectInfos = array_column($subjectInfos, null, 'id');

        $serviceData = new Service_Data_Profile();
        $userInfos = $serviceData->getListByConds(array(sprintf("uid in (%s)", implode(",", $uids))));
        $userInfos = array_column($userInfos, null, 'uid');

        $serviceData = new Service_Data_Birthplace();
        $birthplaces = $serviceData->getBirthplaceByIds($bpdis);
        $birthplaces = array_column($birthplaces, null, 'id');

        $serviceData = new Service_Data_Clasze();
        $claszes = $serviceData->getClaszeByIds($cids);
        $claszes = array_column($claszes, null, 'id');

        $serviceData = new Service_Data_Abroadplan();
        $abroadplanInfos = $serviceData->getAbroadplanByIds($abroadplanIds);
        $abroadplanInfos = array_column($abroadplanInfos, null, 'id');      
        
        $serviceData = new Service_Data_Aporderpackage();
        $apackageInfos = $serviceData->getAbroadpackageByIds($apackageIds);
        $apackageInfos = array_column($apackageInfos, null, 'id');           

        // 排课数
        $orderCounts = array();
        if (!$isSelect) {
            $serviceData = new Service_Data_Curriculum();
            $orderCounts = $serviceData->getScheduleTimeCountByOrder($orderIds);
        }

        $result = array();
        foreach ($lists as $v) {
            if (empty($subjectInfos[$v['subject_id']]['name'])) {
                continue;
            }
            if (empty($userInfos[$v['student_uid']]['nickname'])) {
                continue;
            }
            if (empty($abroadplanInfos[$v['abroadplan_id']]['name'])) {
                continue;
            }
            if (empty($apackageInfos[$v['apackage_id']])) {
                continue;
            }

            // 是否有权限看一些资金相关
            $isModeShowAmount = $this->isOperator(
                Service_Data_Roles::ROLE_MODE_STUDENT_AMOUNT_HANDLE, 
                intval($userInfos[$v['student_uid']]['sop_uid']));            
            
            $extra = json_decode($v['ext'], true);
            $item = array();
            $item['order_id']           = intval($v['order_id']);
            $item['apackage_id']        = intval($v['apackage_id']);
            $item['student_name']       = $userInfos[$v['student_uid']]['nickname'];
            $item['operator_name']      = empty($userInfos[$v['operator']]['nickname']) ? "" : $userInfos[$v['operator']]['nickname'];
            $item['subject_name']       = $subjectInfos[$v['subject_id']]['name'];
            $item['abroadplan_name']    = $abroadplanInfos[$v['abroadplan_id']]['name'];
            $item['student_uid']        = intval($v['student_uid']);
            $item['update_time']        = date("Y年m月d日 H:i",$v['update_time']);
            $item['create_time']        = date("Y年m月d日 H:i",$v['create_time']);
            $item['birthplace']         = empty($birthplaces[$v['bpid']]['name']) ? "" : $birthplaces[$v['bpid']]['name'];
            $item['clasze_name']        = empty($claszes[$v['cid']]['name']) ? "" : $claszes[$v['cid']]['name'];
            $item['schedule_nums']      = $extra['schedule_nums'];
            $item['check_duration']     = empty($orderCounts[$v['order_id']]) ? 0 : floatval(sprintf("%.2f", $orderCounts[$v['order_id']]['c']));
            $item['band_duration']      = empty($orderCounts[$v['order_id']]) ? 0 : floatval(sprintf("%.2f", $orderCounts[$v['order_id']]['a']));
            $item['uncheck_duration']   = empty($orderCounts[$v['order_id']]) ? 0 : floatval(sprintf("%.2f", $orderCounts[$v['order_id']]['u']));
            $item["unband_duration"]    = $item['schedule_nums']  - $item['band_duration'];
            $item["is_amount"]          = $isModeShowAmount ? 1 : 0;
            $item["apackage_state"]     = intval($apackageInfos[$v['apackage_id']]["state"]);

            // 没权限看, 需要展示*
            if (!$isModeShowAmount) {
                $item['unband_duration']    = "***";
                $item['uncheck_duration']   = "***";
                $item['band_duration']      = "***";
                $item['check_duration']     = "***";
                $item['schedule_nums']      = "***";
            }
            $result[] = $item;
        }
        return $result;
    }

    private function formatSelect ($lists) {
        if (empty($lists)) {
            return array();
        }

        $options = array();
        foreach ($lists as $item) {
            $options[] = array(
                'label' => sprintf("%s %s / %s", $item['abroadplan_name'], $item['subject_name'], $item['clasze_name']),
                'value' => intval($item['order_id']),
            );
        }
        return array('options' => array_values($options));
    }
}