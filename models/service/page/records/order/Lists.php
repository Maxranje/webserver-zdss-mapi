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

        $uids = array();
        if (!empty($nickname)) {
            $serviceData = new Service_Data_Profile();

            $conds = array(
                "nickname like '%".$nickname."%'",
            );
            if ($bpid > 0) {
                $conds[] = sprintf("bpid=%d", $bpid);
            }
            $userInfos = $serviceData->getListByConds($conds, array("uid"));
            if (empty($userInfos)) {
                return array();
            }
            $uids = Zy_Helper_Utils::arrayInt($userInfos, "uid");
        }

        $serviceOrder = new Service_Data_Order();
        $conds = array();
        if (!empty($uids)) {
            $conds[] = sprintf("student_uid in (%s)", implode(",", $uids));
        }
        if ($bpid > 0) {
            $conds[] = sprintf("bpid=%d", $bpid);
        }

        if (!empty($dataRange)) {
            $conds[] = sprintf("create_time >= %d", intval($dataRange[0]));
            $conds[] = sprintf("create_time <= %d", intval($dataRange[1]) + 1);
        }
            
        $arrAppends[] = 'order by order_id desc';
        if (!$isExport) {
            $arrAppends[] = "limit {$pn} , {$rn}";
        }
        $lists = $serviceOrder->getListByConds($conds, array(), null, $arrAppends);
        $lists = $this->formatBase($lists);
        if ($isExport) {
            if (count($lists) > 2000) {
                throw new Zy_Core_Exception(405, "操作失败, 受系统限制, 导出的数据不能超过2000条");
            }
            $data = $this->formatExcel($lists);
            Zy_Helper_Utils::exportExcelSimple("payrecords", $data['title'], $data['lists']);
        }
        if (empty($lists)) {
            return array();
        }
        $total = $serviceOrder->getTotalByConds($conds);

        return array(
            'rows' => $lists,
            'total' => $total,
        );
    }

    private function formatBase($lists) {

        if (empty($lists)) {
            return array();
        }

        $subjectIds     = Zy_Helper_Utils::arrayInt($lists, "subject_id");
        $uids           = Zy_Helper_Utils::arrayInt($lists, 'student_uid');
        $orderIds       = Zy_Helper_Utils::arrayInt($lists, 'order_id');
        $cids           = Zy_Helper_Utils::arrayInt($lists, 'cid');
        $bpids          = Zy_Helper_Utils::arrayInt($lists, 'bpid');
        $abroadplanIds  = Zy_Helper_Utils::arrayInt($lists, 'abroadplan_id');

        $serviceData = new Service_Data_Profile();
        $userInfos = $serviceData->getUserInfoByUids($uids);
        $userInfos = array_column($userInfos, null, "uid");

        $serviceData = new Service_Data_Subject();
        $subjectInfos = $serviceData->getSubjectByIds($subjectIds);
        $subjectInfos = array_column($subjectInfos, null, "id");

        $serviceData = new Service_Data_Birthplace();
        $birthplace = $serviceData->getBirthplaceByIds($bpids);
        $birthplace = array_column($birthplace, null, "id");

        $serviceData = new Service_Data_Clasze();
        $claszes = $serviceData->getClaszeByIds($cids);
        $claszes = array_column($claszes, null, "id");

        $serviceData = new Service_Data_Abroadplan();
        $abroadplanInfos = $serviceData->getAbroadplanByIds($abroadplanIds);
        $abroadplanInfos = array_column($abroadplanInfos, null, "id");   

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
            if (!empty($item["abroadplan_id"]) && empty($abroadplanInfos[$item["abroadplan_id"]])) {
                continue;
            }

            // 是否计划订单
            $isApackage = !empty($item["abroadplan_id"]) && !empty($item["apackage_id"]);

            $tmp = array();
            $tmp['update_time']     = date('Y年m月d日', $item['update_time']);
            $tmp['create_time']     = date('Y年m月d日 H:i:s', $item['create_time']);
            $tmp['nickname']        = $userInfos[$item['student_uid']]['nickname'];
            $tmp['order_id']        = $item["order_id"];
            $tmp['subject_name']    = $subjectInfos[$item['subject_id']]['name'];
            $tmp['birthplace']      = $birthplace[$item['bpid']]['name'];
            $tmp['clasze_name']     = $claszes[$item['cid']]['name'];
            $tmp['is_free']         = !empty($item['isfree']) ? 1 : 2;
            $tmp['is_apackage']     = !$isApackage ? "否" : "是";
            $tmp['remark']          = empty($extra['remark']) ? "" : $extra['remark'];
            // 计划订单
            if ($isApackage) {
                $tmp["abroadplan_name"] = $abroadplanInfos[$item["abroadplan_id"]]['name'];
                $tmp['schedule_nums']   = floatval(sprintf("%.2f", $extra['schedule_nums']));
                $tmp['origin_balance']  = "-";
                $tmp['origin_price']    = "-";
                $tmp['real_balance']    = "-";
                $tmp['real_price']      = "-";
                $tmp['balance']         = "-";
                $tmp['change_duration'] = "-";
                $tmp['change_balance']  = "-";
            } else {
                $tmp["abroadplan_name"] = "-";
                $tmp['schedule_nums']   = floatval(sprintf("%.2f", $extra['schedule_nums']));
                $tmp['origin_balance']  = sprintf("%.2f", $extra['origin_balance'] / 100);
                $tmp['origin_price']    = sprintf("%.2f", $extra['origin_price'] / 100);
                $tmp['real_balance']    = !empty($item['isfree']) ? "0.00" : sprintf("%.2f", $extra['real_balance'] / 100);
                $tmp['real_price']      = !empty($item['isfree']) ? "0.00" : sprintf("%.2f", $item['price'] / 100);
                $tmp['balance']         = !empty($item['isfree']) ? "0.00" : sprintf("%.2f", $item['balance'] / 100);
                $tmp['change_duration'] = sprintf("%.2f", $extra['change_balance'] / $item['price']);
                $tmp['change_balance']  = sprintf("%.2f", $extra['change_balance'] / 100);
            }

            $tmp['uncheck_schedule_nums'] = 0;
            $tmp['check_schedule_nums'] = 0;
            $tmp['check_schedule_balance'] = "0.00";
            $tmp['order_state'] = 2;
            if (!empty($orderCounts[$item['order_id']]['u'])) {
                $tmp['uncheck_schedule_nums'] = floatval(sprintf("%.2f", $orderCounts[$item['order_id']]['u']));
                $tmp['order_state'] = 1;
            }
            if (!empty($orderCounts[$item['order_id']]['c'])) {
                $tmp['check_schedule_nums'] = floatval(sprintf("%.2f", $orderCounts[$item['order_id']]['c']));
                if (!$isApackage && !empty($item["isfree"])) {
                    $tmp['check_schedule_balance'] = sprintf("%.2f", ($orderCounts[$item['order_id']]['c'] * $item['price']) / 100);
                }
            }

            $result[] = $tmp;
        }
        return $result;
    }

    private function formatExcel($lists) {
        $result = array(
            'title' => array('日期', '订单ID','学员名', '生源地' , '科目', '班型', "留学计划", "是否免费", '订单课时', '订单原价', '课单价原价', '实际缴费', '惠后单价', '有无排课', "未消课时", '订单当前余额',"已消课时", '已消金额', '结转账户课时', '结转账户金额', "备注", "创建时间"),
            'lists' => array(),
        );
        if (empty($lists)) {
            return $result;
        }
        
        foreach ($lists as $item) {
            $tmp = array(
                $item['update_time'],
                $item["order_id"],
                $item['nickname'],
                $item['birthplace'],
                $item['subject_name'],
                $item['clasze_name'],
                $item["abroadplan_name"],
                $item['is_free'] == 1 ? "免费" : "非免费",
                $item['schedule_nums'],
                $item['origin_balance'],
                $item['origin_price'],
                $item['real_balance'],
                $item['real_price'],
                $item['order_state'] == 1 ? "无排课": "有排课",
                $item['uncheck_schedule_nums'],
                $item['balance'],
                $item["check_schedule_nums"],
                $item["check_schedule_balance"],
                $item['change_duration'],
                $item['change_balance'],
                $item['remark'],
                $item['create_time'],
            );
            $result['lists'][] = $tmp;
        }
        return $result;

    }
}   