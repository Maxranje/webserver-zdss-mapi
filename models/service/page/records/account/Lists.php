<?php

class Service_Page_Records_Account_Lists extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $pn         = empty($this->request['page']) ? 0 : intval($this->request['page']);
        $rn         = empty($this->request['perPage']) ? 0 : intval($this->request['perPage']);
        $nickname   = empty($this->request['nickname']) ? "" : trim($this->request['nickname']);
        $dataRange  = empty($this->request['daterangee']) ? array() : explode(",", $this->request['daterangee']);
        $isExport   = empty($this->request['is_export']) ? false : true;

        $pn = ($pn-1) * $rn;

        $conds = array(
            "state" => 1,  
        );
        if (!empty($nickname)) {
            $serviceData = new Service_Data_Profile();
            $uids = $serviceData->getUserInfoLikeName($nickname);
            if (empty($uids)) {
                return array();
            }
            $uids = Zy_Helper_Utils::arrayInt($uids, "uid");
            $conds[] = sprintf("uid in (%s)", implode(",", $uids));
        }

        if (!empty($dataRange)) {
            $conds[] = sprintf("create_time >= %d", $dataRange[0]);
            $conds[] = sprintf("create_time <= %d", ($dataRange[1] + 1));
        }

        $arrAppends[] = 'order by id desc';
        if (!$isExport) {
            $arrAppends[] = "limit {$pn} , {$rn}";
        }

        $serviceData = new Service_Data_Capital();
        $lists = $serviceData->getListByConds($conds, false, NULL, $arrAppends);
        $lists = $this->formatBase($lists);

        if ($isExport) {
            $data = $this->formatExcel($lists);
            Zy_Helper_Utils::exportExcelSimple("account", $data['title'], $data['lists']);
        }
        if (empty($lists)) {
            return array();
        }

        $total = $serviceData->getTotalByConds($conds);
        return array(
            'rows' => $lists,
            'total' => $total,
        );
    }

    private function formatBase($lists) {

        if (empty($lists)) {
            return array();
        }

        // 充值协作人员
        $partnerUids = array();
        foreach ($lists as $k => $item ) {
            $item['ext'] = empty($item['ext']) ? array() : json_decode($item['ext'], true);
            !empty($item['ext']["partner_uid"]) && $partnerUids[] = intval($item['ext']["partner_uid"]);
            $lists[$k] = $item;
        }

        $operator = Zy_Helper_Utils::arrayInt($lists, 'operator');
        $ropuids = Zy_Helper_Utils::arrayInt($lists, 'rop_uid');
        $uids = Zy_Helper_Utils::arrayInt($lists, 'uid');
        $abroadplanIds = Zy_Helper_Utils::arrayInt($lists, 'abroadplan_id');
        $ids = Zy_Helper_Utils::arrayInt($lists, "id");

        $uids = array_unique(array_merge($uids, $operator, $ropuids, $partnerUids));

        $serviceUsers = new Service_Data_Profile();
        $userInfos = $serviceUsers->getUserInfoByUids($uids);
        $userInfos = array_column($userInfos, null, "uid");

        $serviceData = new Service_Data_Abroadplan();
        $abroadplanInfos = $serviceData->getAbroadplanByIds($abroadplanIds);
        $abroadplanInfos = array_column($abroadplanInfos, null, "id");

        $serviceData = new Service_Data_Review();
        $reviews = $serviceData->getLastReviewByWorkIds($ids);

        foreach ($lists as &$item) {
            $ext = $item['ext'];
            $partnerUid = empty($ext['partner_uid']) ? 0 : intval($ext['partner_uid']);

            $item['type']           = $item['type'] == Service_Data_Profile::RECHARGE ? "1" : "2";
            $item['nickname']       = empty($userInfos[$item['uid']]['nickname']) ? "(已删除)" : $userInfos[$item['uid']]['nickname'];
            $item['operator']       = empty($userInfos[$item['operator']]['nickname']) ? "" :$userInfos[$item['operator']]['nickname'];
            $item['rop_name']       = empty($userInfos[$item['rop_uid']]['nickname']) ? "" :$userInfos[$item['rop_uid']]['nickname'];
            $item['partner']        = $partnerUid > 0 && !empty($userInfos[$partnerUid]['nickname']) ? $userInfos[$partnerUid]['nickname'] : "";
            $item['create_time']    = date("Y年m月d日 H:i:s", $item['create_time']);
            $item['update_time']    = date("Y年m月d日 H:i:s", $item['update_time']);
            $item['capital']        = sprintf("%.2f", $item['capital'] / 100);
            $item['remark']         = empty($ext['remark']) ? "" : $ext['remark'];
            $item['review_remark']  = empty($reviews[$item['id']]['remark']) ? "" : $reviews[$item['id']]['remark'];
            $item["abroadplan_name"]      = empty($abroadplanInfos[$item['abroadplan_id']]['name']) ? "" : $abroadplanInfos[$item['abroadplan_id']]['name'];
            $item["abroadplan_price"]     = empty($abroadplanInfos[$item['abroadplan_id']]['price']) ? "" : sprintf("%.2f", $abroadplanInfos[$item['abroadplan_id']]['price'] / 100);
            $item["refund_balance"] = empty($ext['refund_balance']) || empty($ext['refund_back_balance']) ? "" : sprintf("%.2f", $ext['refund_balance']/ 100);
            $item["refund_back_balance"] = empty($ext['refund_back_balance']) ? "" : sprintf("%.2f", $ext['refund_back_balance'] /100);

            if($item['type'] == Service_Data_Profile::REFUND && empty($item["refund_back_balance"])) {
                $item["refund_balance"] = $item['capital'];
            }
        }
        return $lists;
    }

    private function formatExcel($lists) {
        $result = array(
            'title' => array('日期', 'UID', '用户名', '用户类型', "状态", '实际金额(元)', '充值-计划名称', '充值-计划金额(元)', '退款-退款金额(元)','退款-还款金额(元)',  '操作员', '操作备注', "协作人员",  "审核员", '审核备注',  "更新日期"),
            'lists' => array(),
        );
        if (empty($lists)) {
            return $result;
        }
        
        foreach ($lists as $item) {
            $tmp = array(
                $item['update_time'],
                $item['uid'],
                $item['nickname'],
                $item['type'] == "1" ? "充值" : "退款",
                $item['state'] == "1" ? "审批通过" : ($item['state'] == "2" ? "审批拒绝" : "待审批"),
                $item['capital'],
                $item['abroadplan_name'],
                $item['abroadplan_price'],
                $item['refund_balance'],
                $item['refund_back_balance'],
                $item['operator'],
                $item['remark'],
                $item['partner'],
                $item['rop_name'],
                $item['review_remark'],
                $item['update_time'],
            );
            $result['lists'][] = $tmp;
        }
        return $result;

    }
}