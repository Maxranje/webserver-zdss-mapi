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
        $groupId        = empty($this->request['group_id']) ? 0 : intval($this->request['group_id']);
        $studentUid     = empty($this->request['student_uid']) ? 0 : intval($this->request['student_uid']);
        $state          = empty($this->request['state']) ? 0 : intval($this->request['state']);  // 0 全部, 1已结转, 2已退款, 3没退款和结转
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

        if (in_array($state, [1,2])) {
            $conds[] = $state == 1 ? sprintf("is_transfer=%d", Service_Data_Order::ORDER_DONE) : sprintf("is_refund=%d", Service_Data_Order::ORDER_DONE);
        }

        if ($state == 3) {
            $conds[] = sprintf("is_transfer=%d", Service_Data_Order::ORDER_ABLE);
            $conds[] = sprintf("is_refund=%d", Service_Data_Order::ORDER_ABLE);   
        }

        if ($groupId > 0) {
            $serviceData = new Service_Data_Curriculum();
            $orderIds = $serviceData->getOrderListByGroup(array($groupId));
            $orderIds = Zy_Helper_Utils::arrayInt($orderIds, "order_id");
            if (!empty($orderIds)) {
                $conds[] = sprintf("order_id in (%s)", implode(",", $orderIds));
            }
        }

        if ($scheduleId > 0) {
            $serviceData = new Service_Data_Curriculum();
            $orderIds = $serviceData->getListByConds(array('schedule_id' => $scheduleId));
            $orderIds = Zy_Helper_Utils::arrayInt($orderIds, "order_id");
            if (!empty($orderIds)) {
                $conds[] = sprintf("order_id in (%s)", implode(",", $orderIds));
            }
        }

        $arrAppends[] = 'order by create_time desc';
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

        $serviceData = new Service_Data_Subject();
        $subjectInfos = $serviceData->getListByConds(array(sprintf("id in (%s)", implode(",", $subjectIds))));
        $subjectInfos = array_column($subjectInfos, null, 'id');

        $serviceData = new Service_Data_Profile();
        $studentInfos = $serviceData->getListByConds(array(sprintf("uid in (%s)", implode(",", $studentUids))));
        $studentInfos = array_column($studentInfos, null, 'uid');

        // 排课数
        $serviceData = new Service_Data_Curriculum();
        $orderCounts = $serviceData->getScheduleTimeCountByOrder($orderIds);

        $result = array();
        foreach ($lists as $v) {
            if (empty($subjectInfos[$v['subject_id']]['name'])) {
                continue;
            }
            if (empty($studentInfos[$v['student_uid']]['nickname'])) {
                continue;
            }
            
            $item = array();
            $item['order_id']       = $v['order_id'] ;
            $item['student_name']   = $studentInfos[$v['student_uid']]['nickname'];
            $item['subject_name']   = $subjectInfos[$v['subject_id']]['name'];
            $item['subject_price']  = sprintf("%.2f", $subjectInfos[$v['subject_id']]['price'] / 100);
            $item['pic_name']       = mb_substr($item['subject_name'], 0, 1);
            $item['total_balance']  = sprintf("%.2f", $v['total_balance'] / 100);
            $item['balance']        = sprintf("%.2f", $v['balance'] / 100);
            $item['student_uid']    = intval($v['student_uid']);
            $item['is_transfer']    = intval($v['is_transfer']);
            $item['transfer_id']    = empty($v['transfer_id']) ? "-" : $v['transfer_id'];
            $item['is_refund']      = intval($v['is_refund']);
            $item['refund_id']      = empty($v['refund_id']) ? "-" : $v['refund_id'];
            $item['discount_type']  = $v['discount_type'];
            $item['update_time']    = date("Y年m月d日",$v['update_time']);
            $item['create_time']    = date("Y年m月d日",$v['create_time']);

            $item['schedule_all_count'] = "0";
            $item['schedule_able_count'] = "0";

            $item['discount_type_info'] = "-";
            $item['discount'] = "-";
            $item['discount_price'] = sprintf("%.2f", 0);
            if ($v['discount_type'] == Service_Data_Order::DISCOUNT_Z) {
                $item['discount_type_info'] = ($v['discount'] / 10) . "折";
                $item['discount'] = $v['discount'] / 10;
                $item['discount_price'] = sprintf("%.2f", ($item['subject_price'] * (1 - $v['discount'] / 100)));
            } else if ($v['discount_type'] == Service_Data_Order::DISCOUNT_J) {
                $item['discount_type_info'] = "减免";
                $item['discount'] = $v['discount'] / 100;
                $item['discount_price'] = $v['discount'] / 100;
            }

            if (!empty($orderCounts[$v['order_id']])) {
                $item['schedule_all_count'] = empty($orderCounts[$v['order_id']]['all_count']) ? 0 : sprintf("%.1f", $orderCounts[$v['order_id']]['all_count']);
                $item['schedule_able_count'] = empty($orderCounts[$v['order_id']]['able_count']) ? 0 : sprintf("%.1f", $orderCounts[$v['order_id']]['able_count']);
            }
            
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