<?php

class Service_Page_Records_Order_Listsv2 extends Zy_Core_Service{

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

        if (empty($dataRange[0]) || empty($dataRange[1]) || $dataRange[1] - $dataRange[0] <=0) {
            throw new Zy_Core_Exception(405, "必须选定有效的时间范围");
        }

        $sts = $dataRange[0];
        $ets = $dataRange[1];

        if ($ets - $sts >=  365*86400) {
            throw new Zy_Core_Exception(405, "时间跨度必须一年内");
        }

        // if ($this->adption['type'] == Service_Data_Profile::USER_TYPE_PARTNER) {
        //     $serviceData = new Service_Data_Profile();
        //     $userInfo = $serviceData->getUserInfoByUid(intval($this->adption['userid']));
        //     if (empty($userInfo['bpid']) || intval($userInfo['bpid']) <= 0) {
        //         throw new Zy_Core_Exception(405, "操作失败, 合作方信息不完整, 请联系管理员");
        //     }

        //     if (intval($userInfo['bpid']) != $bpid) {
        //         return array();
        //     }
        // }

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
            
        $arrAppends[] = 'order by order_id desc';
        if (!$isExport) {
            $arrAppends[] = "limit {$pn} , {$rn}";
        }
        $orderLists = $serviceOrder->getListByConds($conds, array(), null, $arrAppends);
        if (empty($orderLists)) {
            if ($isExport) {
                $data = $this->formatExcel(array());
                Zy_Helper_Utils::exportExcelSimple("studentaccount", $data['title'], $data['lists']);
            }
            return array();
        }

        $orderIds = Zy_Helper_Utils::arrayInt($orderLists, "order_id");
        $orderLists = array_column($orderLists, null , "order_id");

        $serviceRecords = new Service_Data_Records();
        $orderChangeLists = $serviceRecords->getOrderChangeListsByOrderIds($orderIds, $sts, $ets);
        if (!empty($orderChangeLists)) {
            foreach ($orderLists as $orderid => &$info) {
                $info['change_duration'] = $info['change_balance'] = 0;
                if (isset($orderChangeLists[$orderid])) {
                    $info['change_duration'] = $orderChangeLists[$orderid]['change_duration'];
                    $info['change_balance'] = $orderChangeLists[$orderid]['change_balance'];
                }
            }
        }

        $recordsLists = $serviceRecords->getRecordsListsByOrderIds($orderIds, $sts, $ets);
        if (!empty($recordsLists)) {
            foreach ($orderLists as $orderid => &$info) {
                $info['checkjob'] = 0;
                if (isset($recordsLists[$orderid])) {
                    $info['checkjob'] = $recordsLists[$orderid]['checkjob'];
                }
            }
        }

        $lists = $this->formatBase($orderLists);
        if ($isExport) {
            $data = $this->formatExcel($lists);
            Zy_Helper_Utils::exportExcelSimple("orderrecords", $data['title'], $data['lists']);
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

        $subjectIds = Zy_Helper_Utils::arrayInt($lists, "subject_id");
        $uids = Zy_Helper_Utils::arrayInt($lists, 'student_uid');
        $cids = Zy_Helper_Utils::arrayInt($lists, 'cid');
        $bpids = Zy_Helper_Utils::arrayInt($lists, 'bpid');

        $serviceUsers = new Service_Data_Profile();
        $userInfos = $serviceUsers->getUserInfoByUids($uids);
        $userInfos = array_column($userInfos, null, "uid");

        $serviceSubject = new Service_Data_Subject();
        $subjectInfos = $serviceSubject->getSubjectByIds($subjectIds);
        $subjectInfos = array_column($subjectInfos, null, "id");

        $serviceBirthplace = new Service_Data_Birthplace();
        $birthplace = $serviceBirthplace->getBirthplaceByIds($bpids);
        $birthplace = array_column($birthplace, null, "id");

        $serviceData = new Service_Data_Clasze();
        $claszes = $serviceData->getClaszeByIds($cids);
        $claszes = array_column($claszes, null, "id");

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
            $tmp['order_id']        = $item['order_id'];
            $tmp['nickname']        = empty($userInfos[$item['student_uid']]['nickname']) ? "" :$userInfos[$item['student_uid']]['nickname'];
            $tmp['subject_name']    = empty($subjectInfos[$item['subject_id']]['name']) ? "" : $subjectInfos[$item['subject_id']]['name'];
            $tmp['birthplace']      = empty($birthplace[$item['bpid']]['name']) ? "" : $birthplace[$item['bpid']]['name'];
            $tmp['clasze_name']     = empty($claszes[$item['cid']]['name']) ? "" : $claszes[$item['cid']]['name'];

            $tmp["order_name"]      = sprintf("%s(%s/%s/%s)", $tmp['nickname'], $tmp['birthplace'], $tmp['subject_name'], $tmp['clasze_name']);
            $tmp['isfree']          = empty($item['isfree']) ? 0 : 1;
            $tmp['schedule_nums']   = sprintf("%.2f", $extra['schedule_nums']);
            $tmp['real_balance']    = !empty($item['isfree']) ? "0.00" : sprintf("%.2f", $extra['real_balance'] / 100);
            $tmp['real_price']      = !empty($item['isfree']) ? "0.00" : sprintf("%.2f", $item['price'] / 100);
            $tmp['balance']         = !empty($item['isfree']) ? "0.00" : sprintf("%.2f", $item['balance'] / 100);
            $tmp['change_duration'] = empty($item['change_duration']) ? "" : sprintf("%.2f", $item['change_duration']);
            $tmp['change_balance']  = empty($item['change_balance']) ? "" : sprintf("%.2f", $item['change_balance'] / 100);
            
            $checkJobBalance = empty($item['checkjob']) ? 0 : intval($item['checkjob']);
            $tmp["check_schedule_nums"] = sprintf("%.2f", $checkJobBalance / $item['price']);
            $tmp["check_schedule_balance"] = !empty($item['isfree'])  ? "0.00" : sprintf("%.2f", $checkJobBalance / 100);

            $result[] = $tmp;
        }
        return $result;
    }

    private function formatExcel($lists) {
        $result = array(
            'title' => array('订单ID', '订单标识', '是否免费' , '订单总课时', '实际缴费', "惠后单价", '订单余额', '结算课时数', '结算课时金额', '结转账户课时', '结转账户金额'),
            'lists' => array(),
        );
        if (empty($lists)) {
            return $result;
        }
        
        foreach ($lists as $item) {
            $tmp = array(
                $item['order_id'],
                $item['order_name'],
                empty($item['isfree']) ? "否" : "是" ,
                $item['schedule_nums'],
                $item['real_balance'],
                $item['real_price'],
                $item['balance'],
                $item['check_schedule_nums'],
                $item['check_schedule_balance'],
                $item['change_duration'],
                $item['change_balance'],
            );
            $result['lists'][] = $tmp;
        }
        return $result;

    }
}   