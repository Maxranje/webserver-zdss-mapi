<?php

class Service_Page_Records_Student_Lists extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $pn         = empty($this->request['page']) ? 0 : intval($this->request['page']);
        $rn         = empty($this->request['perPage']) ? 0 : intval($this->request['perPage']);
        $nickname   = empty($this->request['nickname']) ? "" : trim($this->request['nickname']);
        $uid        = empty($this->request['uid']) ? 0 : intval($this->request['uid']);
        $sopuid     = empty($this->request['sopuid']) ? 0 : intval($this->request['sopuid']);
        $dataRange  = empty($this->request['daterangee']) ? array() : explode(",", $this->request['daterangee']);
        $isExport   = empty($this->request['is_export']) ? false : true;

        $pn = ($pn-1) * $rn;

        if (empty($dataRange[0]) || empty($dataRange[1]) || $dataRange[1] - $dataRange[0] <=0) {
            throw new Zy_Core_Exception(405, "必须选定有效的时间范围");
        }

        $sts = $dataRange[0];
        $ets = $dataRange[1];

        if ($ets - $sts >=  365*86400) {
            throw new Zy_Core_Exception(405, "时间跨度必须一年内");
        }

        $conds = array(
            "type" => Service_Data_Profile::USER_TYPE_STUDENT,
        );
        if (!empty($nickname)) {
            $conds[] = "nickname like '%".$nickname."%'";
        }
        if ($uid > 0) {
            $conds['uid'] = $uid;
        }
        if ($sopuid > 0) {
            $conds['sop_uid'] = $sopuid;
        }

        $arrAppends[] = 'order by uid desc';
        if (!$isExport) {
            $arrAppends[] = "limit {$pn} , {$rn}";
        }

        $serviceData = new Service_Data_Profile();
        $userInfos = $serviceData->getListByConds($conds, array(), null, $arrAppends);
        if (empty($userInfos)) {
            if ($isExport) {
                $data = $this->formatExcel(array());
                Zy_Helper_Utils::exportExcelSimple("studentaccount", $data['title'], $data['lists']);
            }
            return array();
        }

        $serviceRecords = new Service_Data_Records();
        
        $uids = Zy_Helper_Utils::arrayInt($userInfos, "uid");
        $userInfos = array_column($userInfos, null, "uid");

        // 200个uid以上, 按时间获取数据, 
        // 200个uid以下, 按uid时间获取数据, 
        $capitalLists = $serviceRecords->getCapitalListsByUids($uids, $sts, $ets);
        $recordsLists = $serviceRecords->getRecordsListsByUids($uids, $sts, $ets);

        if (!empty($capitalLists)) {
            foreach ($userInfos as $uid => &$info) {
                $info['refund'] = $info['recharge'] = $info['refund_back'] = 0;
                if (isset($capitalLists[$uid])) {
                    $info['recharge'] = $capitalLists[$uid]['recharge'];
                    $info['refund'] = $capitalLists[$uid]['refund'];
                    $info['refund_back'] = $capitalLists[$uid]['refund_back'];
                }
            }
        }

        if (!empty($recordsLists)) {
            foreach ($userInfos as $uid => &$info) {
                $info['checkjob']= 0;
                if (isset($recordsLists[$uid])) {
                    $info['checkjob'] = $recordsLists[$uid]['checkjob'];
                }
            }
        }        

        $lists = $this->formatBase($userInfos);
        
        if ($isExport) {
            $data = $this->formatExcel($lists);
            Zy_Helper_Utils::exportExcelSimple("account", $data['title'], $data['lists']);
        }

        $total = $serviceData->getTotalByConds($conds);
        return array(
            'rows' => $lists,
            'total' => $total,
        );
    }

    private function formatBase($lists) {

        $bpids = Zy_Helper_Utils::arrayInt($lists, 'bpid');
        $sopuids = Zy_Helper_Utils::arrayInt($lists, 'sop_uid');

        $serviceUsers = new Service_Data_Profile();
        $sopUserInfos = $serviceUsers->getUserInfoByUids($sopuids);
        $sopUserInfos = array_column($sopUserInfos, null, "uid");

        $serviceData = new Service_Data_Birthplace();
        $birthplaces = $serviceData->getBirthplaceByIds($bpids);
        $birthplaces = array_column($birthplaces, null, "id");

        $result = array();
        foreach ($lists as $uid => $item) {
            $tmp =array();
            $tmp["uid"]                 = $item['uid'];
            $tmp["nickname"]            = $item['nickname'];
            $tmp["school"]              = $item['school'];
            $tmp["graduate"]            = $item['graduate'];
            $tmp["birthplace"]          = empty($birthplaces[$item['bpid']]['name']) ? "" : $birthplaces[$item['bpid']]['name'];
            $tmp["sopname"]             = empty($sopUserInfos[$item['sop_uid']]['nickname']) ? "" : $sopUserInfos[$item['sop_uid']]['nickname'];
            $tmp['recharge_balance']    = empty($item['recharge']) ? "0.00元" : sprintf("%.2f元", $item['recharge'] / 100);
            $tmp['checkjob_balance']    = empty($item['checkjob']) ? "0.00元" : sprintf("%.2f元", $item['checkjob'] / 100);
            $tmp['refund_balance']      = empty($item['refund']) ? "0.00元" : sprintf("%.2f元", $item['refund'] / 100);
            $tmp['refund_back_balance'] = empty($item['refund_back']) ? "0.00元" : sprintf("%.2f元", $item['refund_back'] / 100);
            $result[] = $tmp;
        }
        return $result;
    }

    private function formatExcel($lists) {
        $result = array(
            'title' => array('学员UID', '学员名', '学校', '年级', '生源地', '学管', '充值金额','结算金额',  '退款金额', '退款还款金额'),
            'lists' => array(),
        );
        if (empty($lists)) {
            return $result;
        }
        
        foreach ($lists as $item) {
            $tmp = array(
                $item["uid"],              
                $item["nickname"],
                $item["school"],           
                $item["graduate"],            
                $item["birthplace"],          
                $item["sopname"],         
                $item['recharge_balance'],
                $item['checkjob_balance'],   
                $item['refund_balance'],   
                $item['refund_back_balance'],
            );
            $result['lists'][] = $tmp;
        }
        return $result;

    }
}