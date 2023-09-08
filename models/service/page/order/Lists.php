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

        if ($isHasbalance) {
            $conds[] = "balance > 0";
        }

        if ($warning == 1) {
            $conds[] = "balance <= 100000 and balance > 0";
        } else if ($warning == 2) {
            $conds[] = "balance > 100000";
        }

        if ($scheduleId > 0) {
            $serviceData = new Service_Data_Curriculum();
            $orderIds = $serviceData->getListByConds(array('schedule_id' => $scheduleId));
            $orderIds = Zy_Helper_Utils::arrayInt($orderIds, "order_id");
            if (!empty($orderIds)) {
                $conds[] = sprintf("order_id in (%s)", implode(",", $orderIds));
            }
        }

        $arrAppends[] = 'order by order_id';
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

        $uids = array_unique(array_merge($studentUids, $operatorIds));

        $serviceData = new Service_Data_Subject();
        $subjectInfos = $serviceData->getListByConds(array(sprintf("id in (%s)", implode(",", $subjectIds))));
        $subjectInfos = array_column($subjectInfos, null, 'id');

        $serviceData = new Service_Data_Profile();
        $userInfos = $serviceData->getListByConds(array(sprintf("uid in (%s)", implode(",", $uids))));
        $userInfos = array_column($userInfos, null, 'uid');

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
            $item['pic_name']       = $v['balance'] <= 0 ? "完结" : ($v['balance'] <= 100000 ? "预警" : "");
            $item['balance']        = sprintf("%.2f", $v['balance'] / 100);
            $item['price']          = sprintf("%.2f", $v['price'] / 100);

            $item['origin_balance'] = sprintf("%.2f", $extra['origin_balance'] / 100);
            $item['real_balance']   = sprintf("%.2f", $extra['real_balance'] / 100);
            $item['origin_price']   = sprintf("%.2f", $extra['origin_price'] / 100);
            $item['real_price']     = sprintf("%.2f", $extra['real_price'] / 100);
            $item['schedule_nums']  = $extra['schedule_nums'];
            $item['discount_info']  = "-";
            if ($v['discount_type'] == Service_Data_Order::DISCOUNT_Z) {
                $item['discount_info'] = "折扣(" . $v['discount'] . "%)";
            } else if ($v['discount_type'] == Service_Data_Order::DISCOUNT_J) {
                $item['discount_info'] = sprintf("减免(%.2f元)", $v['discount'] / 100);
            }

            $item['last_duration']      = sprintf("%.2f", $v['balance'] / $v['price']);
            $item['check_duration']     = sprintf("%.2f", $orderCounts[$v['order_id']]['c']);
            $item['band_duration']      = sprintf("%.2f", $orderCounts[$v['order_id']]['a']);
            $item['band_uncheck_duration'] = sprintf("%.2f", $orderCounts[$v['order_id']]['u']);
            $item['transfer_id']        =  $v['transfer_id'];
            $item['transfer_balance']   = empty($extra['transfer_balance']) ? "0.00" : sprintf("%.2f", $extra['transfer_balance'] / 100);
            $item['refund_balance']     = empty($extra['refund_balance']) ? "0.00" : sprintf("%.2f", $extra['refund_balance'] / 100);
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
                'label' => sprintf("订单ID:%s, 科目:%s", $item['order_id'], $item['subject_name']),
                'value' => intval($item['order_id']),
            );
        }
        return array('options' => array_values($options));
    }
}