<?php

class Service_Page_Records_Order_Lists extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $pn         = empty($this->request['page']) ? 0 : intval($this->request['page']);
        $rn         = empty($this->request['perPage']) ? 0 : intval($this->request['perPage']);
        $nickname   = empty($this->request['nickname']) ? "" : trim($this->request['nickname']);
        $bpid       = empty($this->request['bpid']) ? 0 : intval($this->request['bpid']);
        $dataRange  = empty($this->request['daterangee']) ? array() : explode(",", $this->request['daterangee']);
        $isExport   = empty($this->request['is_export']) ? false : true;

        $pn = ($pn-1) * $rn;

        if (!empty($nickname) && (mb_strlen($nickname) > 10 || !Zy_Helper_Utils::checkStr($nickname))) {
            throw new Zy_Core_Exception(405, "操作失败, 输入存在非法字符或长度超过10");
        }

        if ($this->adption['type'] == Service_Data_Profile::USER_TYPE_PARTNER) {
            $serviceData = new Service_Data_Profile();
            $userInfo = $serviceData->getUserInfoByUid(intval($this->adption['userid']));
            if (empty($userInfo['bpid']) || intval($userInfo['bpid']) <= 0) {
                throw new Zy_Core_Exception(405, "操作失败, 合作方信息不完整, 请联系管理员");
            }

            if (intval($userInfo['bpid']) != $bpid) {
                return array();
            }
        }

        $serviceOrder = new Service_Data_Order();
        if (!empty($nickname) || $bpid > 0) {
            $params = array(
                'nickname' => $nickname,
                'bpid' => $bpid,
                "is_export" => $isExport,
                "pn" => $pn,
                'rn' => $rn,
            );
            if (!empty($dataRange)) {
                $params['start_time'] = intval($dataRange[0]);
                $params['end_time'] = intval($dataRange[1]) + 1;
            }
            $lists = $serviceOrder->getDataList($params);
            $lists = $this->formatBasev1($lists);
            if ($isExport) {
                $data = $this->formatExcel($lists);
                Zy_Helper_Utils::exportExcelSimple("payrecords", $data['title'], $data['lists']);
            }
            if (empty($lists)) {
                return array();
            }
            $total = $serviceOrder->getDataTotal($params);            
        } else {
            $conds = array();
            if (!empty($dataRange)) {
                $conds[] = sprintf("create_time >= %d", intval($dataRange[0]));
                $conds[] = sprintf("create_time <= %d", intval($dataRange[1]) + 1);
            }
            
            $arrAppends[] = 'order by order_id desc';
            if (!$isExport) {
                $arrAppends[] = "limit {$pn} , {$rn}";
            }
            $lists = $serviceOrder->getListByConds($conds, array(), null, $arrAppends);
            $lists = $this->formatBasev2($lists);
            if ($isExport) {
                $data = $this->formatExcel($lists);
                Zy_Helper_Utils::exportExcelSimple("payrecords", $data['title'], $data['lists']);
            }
            if (empty($lists)) {
                return array();
            }
            $total = $serviceOrder->getTotalByConds($conds);

        }
        return array(
            'rows' => $lists,
            'total' => $total,
        );
    }

    private function formatBasev2($lists) {

        if (empty($lists)) {
            return array();
        }

        $subjectIds = Zy_Helper_Utils::arrayInt($lists, "subject_id");
        $uids = Zy_Helper_Utils::arrayInt($lists, 'student_uid');
        $orderIds = Zy_Helper_Utils::arrayInt($lists, 'order_id');

        $serviceUsers = new Service_Data_Profile();
        $userInfos = $serviceUsers->getUserInfoByUids($uids);
        $userInfos = array_column($userInfos, null, "uid");

        $serviceSubject = new Service_Data_Subject();
        $subjectInfos = $serviceSubject->getSubjectByIds($subjectIds);
        $subjectInfos = array_column($subjectInfos, null, "id");

        $birthplaceIds = Zy_Helper_Utils::arrayInt($userInfos, "bpid");
        $serviceBirthplace = new Service_Data_Birthplace();
        $birthplace = $serviceBirthplace->getBirthplaceByIds($birthplaceIds);
        $birthplace = array_column($birthplace, null, "id");

        // 排课数
        $serviceData = new Service_Data_Curriculum();
        $orderCounts = $serviceData->getScheduleTimeCountByOrder($orderIds);

        $result = array();
        foreach ($lists as $item) {
            if (empty($userInfos[$item['student_uid']]['nickname'])) {
                continue;
            }
            $extra = json_decode($item['ext'], true);
            if (empty($extra)) {
                continue;
            }

            $tmp = array();
            $tmp['update_time']     = date('Y年m月d日', $item['update_time']);
            $tmp['create_time']     = date('Y年m月d日 H:i:s', $item['create_time']);
            $tmp['nickname']        = $userInfos[$item['student_uid']]['nickname'];
            $tmp['subject_name']    = $subjectInfos[$item['subject_id']]['name'];
            $tmp['birthplace']      = $birthplace[$userInfos[$item['student_uid']]['bpid']]['name'];
            $tmp['schedule_nums']   = sprintf("%.2f", $extra['schedule_nums']);
            $tmp['origin_balance']  = sprintf("%.2f", $extra['origin_balance'] / 100);
            $tmp['origin_price']    = sprintf("%.2f", $extra['origin_price'] / 100);
            $tmp['real_balance']    = sprintf("%.2f", $extra['real_balance'] / 100);
            $tmp['real_price']      = sprintf("%.2f", $item['price'] / 100);
            $tmp['balance']         = sprintf("%.2f", $item['balance'] / 100);
            $tmp['transfer_schedule_nums'] = sprintf("%.2f", $extra['transfer_balance'] / $item['price']);
            $tmp['transfer_balance'] = sprintf("%.2f", $extra['transfer_balance'] / 100);
            $tmp['refund_schedule_nums'] = sprintf("%.2f", $extra['refund_balance'] / $item['price']);
            $tmp['refund_balance'] = sprintf("%.2f", $extra['refund_balance'] / 100);
            $tmp['uncheck_schedule_nums'] = "0.00";
            $tmp['order_state'] = "无排课";
            if (!empty($orderCounts[$item['order_id']]['u'])) {
                $tmp['uncheck_schedule_nums'] = sprintf("%.2f", $orderCounts[$item['order_id']]['u']);
                $tmp['order_state'] = "有排课";
            }

            $result[] = $tmp;
        }
        return $result;
    }

    private function formatBasev1($lists) {

        if (empty($lists)) {
            return array();
        }

        $subjectIds = Zy_Helper_Utils::arrayInt($lists, "subject_id");
        $orderIds = Zy_Helper_Utils::arrayInt($lists, 'order_id');
        $birthplaceIds = Zy_Helper_Utils::arrayInt($lists, 'bpid');

        $serviceSubject = new Service_Data_Subject();
        $subjectInfos = $serviceSubject->getSubjectByIds($subjectIds);
        $subjectInfos = array_column($subjectInfos, null, "id");

        $serviceBirthplace = new Service_Data_Birthplace();
        $birthplace = $serviceBirthplace->getBirthplaceByIds($birthplaceIds);
        $birthplace = array_column($birthplace, null, "id");

        // 排课数
        $serviceData = new Service_Data_Curriculum();
        $orderCounts = $serviceData->getScheduleTimeCountByOrder($orderIds);

        $result = array();
        foreach ($lists as $item) {
            $extra = json_decode($item['ext'], true);
            if (empty($extra)) {
                continue;
            }

            $tmp = array();
            $tmp['update_time']     = date('Y年m月d日', $item['update_time']);
            $tmp['create_time']     = date('Y年m月d日 H:i:s', $item['create_time']);
            $tmp['nickname']        = $item['nickname'];
            $tmp['subject_name']    = $subjectInfos[$item['subject_id']]['name'];
            $tmp['birthplace']      = $birthplace[$item['bpid']]['name'];
            $tmp['schedule_nums']   = sprintf("%.2f", $extra['schedule_nums']);
            $tmp['origin_balance']  = sprintf("%.2f", $extra['origin_balance'] / 100);
            $tmp['origin_price']    = sprintf("%.2f", $extra['origin_price'] / 100);
            $tmp['real_balance']    = sprintf("%.2f", $extra['real_balance'] / 100);
            $tmp['real_price']      = sprintf("%.2f", $item['price'] / 100);
            $tmp['balance']         = sprintf("%.2f", $item['balance'] / 100);
            $tmp['transfer_schedule_nums'] = sprintf("%.2f", $extra['transfer_balance'] / $item['price']);
            $tmp['transfer_balance'] = sprintf("%.2f", $extra['transfer_balance'] / 100);
            $tmp['refund_schedule_nums'] = sprintf("%.2f", $extra['refund_balance'] / $item['price']);
            $tmp['refund_balance'] = sprintf("%.2f", $extra['refund_balance'] / 100);
            $tmp['uncheck_schedule_nums'] = "0.00";
            $tmp['order_state'] = "无排课";
            if (!empty($orderCounts[$item['order_id']]['u'])) {
                $tmp['uncheck_schedule_nums'] = sprintf("%.2f", $orderCounts[$item['order_id']]['u']);
                $tmp['order_state'] = "有排课";
            }

            $result[] = $tmp;
        }
        return $result;
    }

    private function formatExcel($lists) {
        $result = array(
            'title' => array('日期', '学员名', '生源地' , '科目','订单课时', '订单原价', '课单价原价', '实际缴费', '惠后单价', '订单状态', "未消课时", '订单当前余额', '结转课时', '结转金额', "退款课时", "退款金额", "创建时间"),
            'lists' => array(),
        );
        if (empty($lists)) {
            return $result;
        }
        
        foreach ($lists as $item) {
            $tmp = array(
                $item['update_time'],
                $item['nickname'],
                $item['birthplace'],
                $item['subject_name'],
                $item['schedule_nums'],
                $item['origin_balance'],
                $item['origin_price'],
                $item['real_balance'],
                $item['real_price'],
                $item['order_state'],
                $item['uncheck_schedule_nums'],
                $item['balance'],
                $item['transfer_schedule_nums'],
                $item['transfer_balance'],
                $item['refund_schedule_nums'],
                $item['refund_balance'],
                $item['create_time'],
            );
            $result['lists'][] = $tmp;
        }
        return $result;

    }
}