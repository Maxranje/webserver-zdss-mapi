<?php

class Service_Page_Review_Lists extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $pn         = empty($this->request['page']) ? 1 : intval($this->request['page']);
        $rn         = empty($this->request['perPage']) ? 20 : intval($this->request['perPage']);
        $uid        = empty($this->request['uid']) ? 0 : intval($this->request['uid']);
        $type       = empty($this->request['type']) ? 0 : intval($this->request['type']);
        $state      = empty($this->request['state']) ? 0 : intval($this->request['state']);
        $dataRange  = empty($this->request['daterangee']) ? array() : explode(",", $this->request['daterangee']);

        $sopuid = OPERATOR;
        if ($this->isModeAble(Service_Data_Roles::ROLE_MODE_REVIEW_HANDLE)) {
            $sopuid = 0;
        }

        $pn = ($pn-1) * $rn;

        $conds = array();
        if ($type > 0) {
            $conds["type"] = $type;
        }
        if ($uid > 0) {
            $conds['uid'] = $uid;
        }
        if ($state > 0) {
            $conds["state"] = $state;
        }
        if ($sopuid > 0) {
            $conds['sop_uid'] = $sopuid;
        }
        if (!empty($dataRange)) {
            $conds[] = sprintf("create_time >= %d", intval($dataRange[0]));
            $conds[] = sprintf("create_time <= %d", intval($dataRange[1]) + 1);
        }
        $arrAppends = array(
            'order by state desc,id desc',
            "limit {$pn} , {$rn}"
        );

        $serviceReview = new Service_Data_Review();
        $lists = $serviceReview->getListByConds($conds, array(), NULL, $arrAppends);
        $lists = $this->formatBase($lists);
        if (empty($lists)) {
            return array();
        }

        $total = $serviceReview->getTotalByConds($conds);

        return array(
            'lists' => $lists,
            'total' => $total,
        );
    }

    private function formatBase($lists) {
        if (empty($lists)) {
            return array();
        }

        $workIds = Zy_Helper_Utils::arrayInt($lists, "work_id");
        $sopuids = Zy_Helper_Utils::arrayInt($lists, "sop_uid");
        $ropuids = Zy_Helper_Utils::arrayInt($lists, "rop_uid");
        $uids = Zy_Helper_Utils::arrayInt($lists, "uid");

        $uids = array_unique(array_merge($uids, $ropuids, $sopuids));
        
        // 获取订单资源详情
        $serviceData = new Service_Data_Capital();
        $capitals = $serviceData->getCapitalByIds($workIds);
        $planIds = Zy_Helper_Utils::arrayInt($capitals, "plan_id");
        $capitals = array_column($capitals, null, "id");

        // 获取计划信息
        $serviceData = new Service_Data_Plan();
        $planInfos = $serviceData->getPlanByIds($planIds);
        $planInfos = array_column($planInfos, null, "id");

        // 获取人员信息
        $serviceData = new Service_Data_Profile();
        $userInfos = $serviceData->getUserInfoByUids($uids);
        $userInfos = array_column($userInfos, null, "uid");

        $isRd = $this->isModeAble(Service_Data_Roles::ROLE_MODE_REVIEW_HANDLE);

        $result = array();
        foreach ($lists as $item) {
            if (empty($userInfos[$item['uid']]['name'])) {
                continue;
            }
            $capital = empty($capitals[$item['work_id']]) ? array() : $capitals[$item['work_id']];
            if (empty($capital)) {
                continue;
            }

            $capitalExt = empty($capital['ext']) ? array() : json_decode($capital['ext'], true);

            $tmp = array();
            $tmp["is_rd"]           = $isRd ? 1 : 0;
            $tmp['create_time']     = date("Y/m/d H:i:s", $item['create_time']);
            $tmp["id"]              = $item["id"];
            $tmp["uid"]             = $item["uid"];
            $tmp["nickname"]        = $userInfos[$item['uid']]['nickname'];
            $tmp["type"]            = $item["type"];
            $tmp["state"]           = intval($item["state"]);
            $tmp["remark"]          = trim($item["remark"]);
            $tmp["work_info"]       = array(
                "类型"               => $item["type"] == Service_Data_Review::REVIEW_TYPE_RECHARGE? "充值":"退款",
                "总金额"             => sprintf("%.2f元", $capital['capital'] / 100),
            );
            $tmp['sop_name']        = empty($userInfos[$item['sop_uid']]['name']) ? "" : $userInfos[$item['sop_uid']]['name'];
            $tmp['review_name']     = empty($userInfos[$item['rop_uid']]['name']) ? "" : $userInfos[$item['rop_uid']]['name'];
            if ($item['type'] == Service_Data_Review::REVIEW_TYPE_RECHARGE) {
                if (!empty($capital['plan_id']) && !empty($planInfos[$capital['plan_id']]['name'])) {
                    $tmp["work_info"]["留学与升学服务计划"] = $planInfos[$capital['plan_id']]['name'];
                }
            } else {
                $ext = empty($capital['ext']) ? array() : json_decode($capital['ext'], true);
                if (!empty($ext["refund_balance"])) {
                    $tmp["work_info"]["退款金额"] = sprintf("%.2f元", $ext['refund_balance'] / 100);
                }
                if (!empty($ext["refund_back_balance"])) {
                    $tmp["work_info"]["退款扣款金额"] = sprintf("%.2f元", $ext['refund_back_balance'] / 100);
                }
            }
            $tmp['work_info']['备注'] = empty($capitalExt['remark']) ? "" : $capitalExt['remark'];
            $result[] = $tmp;
        }

        return $result;
    }
}