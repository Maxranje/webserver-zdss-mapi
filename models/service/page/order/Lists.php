<?php

class Service_Page_Order_Lists extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $pn             = empty($this->request['page']) ? 1 : intval($this->request['page']);
        $rn             = empty($this->request['perPage']) ? 20 : intval($this->request['perPage']);
        $orderId        = empty($this->request['order_id']) ? 0 : intval($this->request['order_id']);
        $scheduleId     = empty($this->request['schedule_id']) ? 0 : intval($this->request['schedule_id']);
        $filterId       = empty($this->request['filter_id']) ? 0 : intval($this->request['filter_id']); // is_select时候需要过滤的.
        $subjectId      = empty($this->request['subject_id']) ? 0 : intval($this->request['subject_id']);
        $birthplace     = empty($this->request['birthplace']) ? 0 : intval($this->request['birthplace']);
        $claszeId       = empty($this->request['clasze_id']) ? 0 : intval($this->request['clasze_id']);
        $studentUid     = empty($this->request['student_uid']) ? 0 : intval($this->request['student_uid']);
        $warning        = empty($this->request['warning']) ? 0 : intval($this->request['warning']);
        $isHasbalance   = empty($this->request['is_hasbalance']) ? false : true;
        $isSelect       = empty($this->request['is_select']) ? false : true;
        $pn             = ($pn-1) * $rn;

        $conds = array();

        if ($orderId > 0) {
            $conds['order_id'] = $orderId;
        }

        if ($studentUid > 0) {
            $conds['student_uid'] = $studentUid;
        }

        if ($subjectId > 0) {
            $conds['subject_id'] = $subjectId;
        }

        if ($claszeId > 0) {
            $conds['cid'] = $claszeId;
        }

        if ($birthplace > 0) {
            $conds['bpid'] = $birthplace;
        }

        if ($isHasbalance) {
            $conds[] = "balance > 0";
        }

        if ($warning == 1) {
            $conds[] = sprintf("balance <= %d and balance > 0", Service_Data_Order::WARNING_BALANCE);
        } else if ($warning == 2) {
            $conds[] = "isfree=1";
        } else if ($warning == 3) {
            $conds[] = "balance <= 0";
        }

        if ($scheduleId > 0) {
            $serviceData = new Service_Data_Curriculum();
            $orderIds = $serviceData->getListByConds(array('schedule_id' => $scheduleId));
            $orderIds = Zy_Helper_Utils::arrayInt($orderIds, "order_id");
            if (!empty($orderIds)) {
                $conds[] = sprintf("order_id in (%s)", implode(",", $orderIds));
            }
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
        
        $lists = $this->formatDefault($lists);
        if ($isSelect) {
            return $this->formatSelect($lists, $filterId);
        }
        
        $total = $serviceData->getTotalByConds($conds);
        return array(
            'rows' => $lists,
            'total' => $total,
        );
    }

    // 默认格式化
    private function formatDefault ($lists) {
        $studentUids = Zy_Helper_Utils::arrayInt($lists, 'student_uid');
        $subjectIds  = Zy_Helper_Utils::arrayInt($lists, 'subject_id');
        $orderIds    = Zy_Helper_Utils::arrayInt($lists, 'order_id');
        $operatorIds = Zy_Helper_Utils::arrayInt($lists, 'operator');
        $bpdis       = Zy_Helper_Utils::arrayInt($lists, 'bpid');
        $cids        = Zy_Helper_Utils::arrayInt($lists, 'cid');

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

        // 排课数
        $serviceData = new Service_Data_Curriculum();
        $orderCounts = $serviceData->getScheduleTimeCountByOrder($orderIds);

        $result = array();
        foreach ($lists as $v) {
            if (empty($subjectInfos[$v['subject_id']]['name'])) {
                continue;
            }
            if (empty($userInfos[$v['student_uid']]['nickname'])) {
                continue;
            }
            
            $extra = json_decode($v['ext'], true);
            $item = array();
            $item['order_id']       = $v['order_id'] ;
            $item['student_name']   = $userInfos[$v['student_uid']]['nickname'];
            $item['operator_name']  = $userInfos[$v['operator']]['nickname'];
            $item['subject_name']   = $subjectInfos[$v['subject_id']]['name'];
            $item['student_uid']    = intval($v['student_uid']);
            $item['update_time']    = date("Y年m月d日 H:i",$v['update_time']);
            $item['create_time']    = date("Y年m月d日 H:i",$v['create_time']);
            if ($v['balance'] <= 0) {
                $item['pic_name'] = "完结";
            } else if ($v['isfree'] == 1) {
                $item['pic_name'] = "免费";
            } else if ($v['balance'] <= Service_Data_Order::WARNING_BALANCE) {
                $item['pic_name'] = "预警";
            } else {
                $item['pic_name'] = "";
            }
            $item['balance']        = sprintf("%.2f", $v['balance'] / 100);
            $item['price']          = sprintf("%.2f", $v['price'] / 100);

            $item['birthplace']     = empty($birthplaces[$v['bpid']]['name']) ? "" : $birthplaces[$v['bpid']]['name'];
            $item['clasze_name']    = empty($claszes[$v['cid']]['name']) ? "" : $claszes[$v['cid']]['name'];
            $item['origin_balance'] = sprintf("%.2f", $extra['origin_balance'] / 100);
            $item['real_balance']   = sprintf("%.2f", $extra['real_balance'] / 100);
            $item['origin_price']   = sprintf("%.2f", $extra['origin_price'] / 100);
            $item['real_price']     = sprintf("%.2f", $extra['real_price'] / 100);
            $item['schedule_nums']  = $extra['schedule_nums'];
            $item['isfree']         = empty($v['isfree']) ? 0 : 1;
            $item['discount_info']  = "";
            if (!empty($v['discount_z'])) {
                $item['discount_info'] .= "折扣(" . $v['discount_z'] . "%) ";
            } 
            if (!empty($v['discount_j'])) {
                $item['discount_info'] .= sprintf("减免(%.2f元)", $v['discount_j'] / 100);
            }

            $item['check_duration']     = sprintf("%.2f", $orderCounts[$v['order_id']]['c']);
            $item['band_duration']      = sprintf("%.2f", $orderCounts[$v['order_id']]['a']);
            $item['uncheck_duration']   = sprintf("%.2f", $orderCounts[$v['order_id']]['u']);

            $item['last_balance']       = sprintf("%.2f", ($v['balance'] - ($orderCounts[$v['order_id']]['u'] * $v['price'])) / 100);
            $item['unband_duration']    = sprintf("%.2f", ($v['balance'] - ($orderCounts[$v['order_id']]['u'] * $v['price'])) / $v['price']);
            $item['change_balance']     = empty($extra['change_balance']) ? "0.00" : sprintf("%.2f", $extra['change_balance'] / 100);
            $item['remark']             = empty($extra['remark']) ? "" : $extra['remark'];
            $result[] = $item;
        }
        return $result;
    }

    private function formatSelect ($lists, $filterId = 0) {
        if (empty($lists)) {
            return array();
        }

        $options = array();
        foreach ($lists as $item) {
            if ($filterId > 0 && $item['order_id'] == $filterId) {
                continue;
            }
            $options[] = array(
                'label' => sprintf("%s %s / %s", $item['order_id'], $item['subject_name'], $item['clasze_name']),
                'value' => intval($item['order_id']),
            );
        }
        return array('options' => array_values($options));
    }
}