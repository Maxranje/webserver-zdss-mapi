<?php

class Service_Page_Review_Lists extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $pn         = empty($this->request['page']) ? 1 : intval($this->request['page']);
        $rn         = empty($this->request['perPage']) ? 20 : intval($this->request['perPage']);
        $id         = empty($this->request['id']) ? 0 : intval($this->request['id']);
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
        if ($id > 0) {
            $conds["id"] = $id;
        }
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
            'order by id desc',
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

        $sopuids = Zy_Helper_Utils::arrayInt($lists, "sop_uid");
        $ropuids = Zy_Helper_Utils::arrayInt($lists, "rop_uid");
        $uids = Zy_Helper_Utils::arrayInt($lists, "uid");

        $uids = array_unique(array_merge($uids, $ropuids, $sopuids));

        // 提前拉取所有计划订单信息
        $serviceData = new Service_Data_Abroadplan();
        $abroadplanInfos = $serviceData->getListByConds(array("id>0"));
        $abroadplanInfos = array_column($abroadplanInfos, null, "id");
        
        // 分离开workerid
        $workApackage = $workCapital = array();
        foreach ($lists as $item) {
            if ($item["type"] == Service_Data_Review::REVIEW_TYPE_RECHARGE ||
                $item["type"] == Service_Data_Review::REVIEW_TYPE_REFUND) { // 充值和退款的workids
                $workCapital[] = intval($item["work_id"]);
            } else if ($item["type"] == Service_Data_Review::REVIEW_TYPE_APACKAGE_CREATE || 
                $item["type"] == Service_Data_Review::REVIEW_TYPE_APACKAGE_DURATION || 
                $item["type"] == Service_Data_Review::REVIEW_TYPE_APACKAGE_DONE || 
                $item["type"] == Service_Data_Review::REVIEW_TYPE_APACKAGE_TRANSFER){
                $workApackage[] = intval($item["work_id"]);
            }
        }

        // 充值或退款明细
        $capitalInfos = array();
        if (!empty($workCapital)) {
            $serviceData = new Service_Data_Capital();
            $capitalInfos = $serviceData->getCapitalByIds($workCapital);
            $capitalInfos = array_column($capitalInfos, null, "id");
        }

        // 订单明细
        $apackageInfos = array();
        if (!empty($workApackage)) {
            $serviceData = new Service_Data_Aporderpackage();
            $apackageInfos = $serviceData->getAbroadpackageByIds($workApackage);
            $apackageInfos = array_column($apackageInfos, null, "id");
        }

        // 协作方
        $partnerUids = array();
        foreach ($capitalInfos as $id => $item ) {
            $item['ext'] = empty($item['ext']) ? array() : json_decode($item['ext'], true);
            !empty($item['ext']["partner_uid"]) && $partnerUids[] = intval($item['ext']["partner_uid"]);

            $capitalInfos[$id] = $item;
        }
        if (count($partnerUids) > 0) {
            $uids = array_unique(array_merge($uids, $partnerUids));
        }

        // 获取人员信息
        $serviceData = new Service_Data_Profile();
        $userInfos = $serviceData->getUserInfoByUids($uids);
        $userInfos = array_column($userInfos, null, "uid");

        $isRd = $this->isModeAble(Service_Data_Roles::ROLE_MODE_REVIEW_HANDLE);

        $result = array();
        foreach ($lists as $item) {
            if (empty($userInfos[$item['uid']]['nickname'])) {
                continue;
            }

            $workId = $item['work_id'];
            $capital    = empty($capitalInfos[$workId]) ? array() : $capitalInfos[$workId];
            $apackage   = empty($apackageInfos[$workId]) ? array() : $apackageInfos[$workId];
            if ((in_array($item["type"], [1,2]) && empty($capital)) || 
                in_array($item["type"], [3,4,5,6]) && empty($apackage)){
                continue;
            }

            $tmp = array();
            $tmp["is_rd"]           = $isRd ? 1 : 0;
            $tmp['create_time']     = date("Y/m/d H:i:s", $item['create_time']);
            $tmp["id"]              = $item["id"];
            $tmp["uid"]             = $item["uid"];
            $tmp["nickname"]        = $userInfos[$item['uid']]['nickname'];
            $tmp["type"]            = $item["type"];
            $tmp["state"]           = intval($item["state"]);
            $tmp["remark"]          = trim($item["remark"]);
            $tmp['sop_name']        = empty($userInfos[$item['sop_uid']]['nickname']) ? "" : $userInfos[$item['sop_uid']]['nickname'];
            $tmp['review_name']     = empty($userInfos[$item['rop_uid']]['nickname']) ? "" : $userInfos[$item['rop_uid']]['nickname'];

            // 充值
            if ($item['type'] == Service_Data_Review::REVIEW_TYPE_RECHARGE) {
                $tmp["work_info"] = array(
                    "类型"      => "充值",
                    "总金额"    => sprintf("%.2f元", $capital['capital'] / 100),
                );
                $capitalExt = $capital['ext'];
                // 计划信息
                $abroadplanId = !empty($capital['abroadplan_id']) ? $capital['abroadplan_id'] : 0;
                if ($abroadplanId > 0 && !empty($abroadplanInfos[$abroadplanId]['name'])) {
                    $tmp["work_info"]["留学与升学服务计划"] = $abroadplanInfos[$abroadplanId]['name'];
                }
                // 协作人员
                $partnerUid = !empty($capitalExt['partner_uid']) ? $capitalExt['partner_uid'] : 0;
                if ($partnerUid > 0 && !empty($userInfos[$partnerUid]['nickname'])) {
                    $tmp["work_info"]["协作人员"] = $userInfos[$partnerUid]['nickname'];
                }
                $tmp['work_info']['备注'] = empty($capitalExt['remark']) ? "" : $capitalExt['remark'];
            } else if ($item['type'] == Service_Data_Review::REVIEW_TYPE_REFUND) {
                $tmp["work_info"] = array(
                    "类型"      => "退款",
                    "总金额"    => sprintf("%.2f元", $capital['capital'] / 100),
                );
                $capitalExt = $capital['ext'];
                if (!empty($capitalExt["refund_balance"])) {
                    $tmp["work_info"]["退款金额"] = sprintf("%.2f元", $capitalExt['refund_balance'] / 100);
                }
                if (!empty($capitalExt["refund_back_balance"])) {
                    $tmp["work_info"]["退款扣款金额"] = sprintf("%.2f元", $capitalExt['refund_back_balance'] / 100);
                }
                $tmp['work_info']['备注'] = empty($capitalExt['remark']) ? "" : $capitalExt['remark'];
            } else if ($item['type'] == Service_Data_Review::REVIEW_TYPE_APACKAGE_CREATE) {
                $tmp["work_info"] = array(
                    "类型"      => "计划服务创建",
                );
                // 计划信息
                $abroadplanId = !empty($apackage['abroadplan_id']) ? $apackage['abroadplan_id'] : 0;
                if ($abroadplanId > 0 && !empty($abroadplanInfos[$abroadplanId]['name'])) {
                    $tmp["work_info"]["留学&升学服务计划"] = $abroadplanInfos[$abroadplanId]['name'];
                    $tmp["work_info"]["留学&升学服务计划金额"] = sprintf("%.2f元", $abroadplanInfos[$abroadplanId]['price'] / 100);
                    $tmp["work_info"]["留学&升学服务计划课时"] = $abroadplanInfos[$abroadplanId]['duration'] . "小时";
                    $tmp["work_info"]["服务缴费金额"] = sprintf("%.2f元", $apackage["price"] / 100);
                }
                $tmp['work_info']['备注'] = empty($apackage['remark']) ? "" : $apackage['remark'];
            } else if ($item['type'] == Service_Data_Review::REVIEW_TYPE_APACKAGE_DURATION) {
                $tmp["work_info"] = array(
                    "类型"      => "计划服务增加课时",
                );
                // 计划信息
                $abroadplanId = !empty($apackage['abroadplan_id']) ? $apackage['abroadplan_id'] : 0;
                $reviewExt = empty($item['ext']) ? array() : json_decode($item["ext"], true);
                if ($abroadplanId > 0 && !empty($abroadplanInfos[$abroadplanId]['name'])) {
                    $tmp["work_info"]["服务ID"] = intval($item["work_id"]);
                    $tmp["work_info"]["归属留学计划"] = $abroadplanInfos[$abroadplanId]['name'];
                    $tmp["work_info"]["服务调整课时数"] = !empty($reviewExt["schedule_nums"]) ? sprintf("%.2f小时", $reviewExt["schedule_nums"]) : "-";
                }
                $tmp['work_info']['备注'] = empty($reviewExt['remark']) ? "" : $reviewExt['remark'];
            }else if ($item['type'] == Service_Data_Review::REVIEW_TYPE_APACKAGE_DONE) {
                $tmp["work_info"] = array(
                    "类型"      => "计划服务完结",
                );
                // 计划信息
                $abroadplanId = !empty($apackage['abroadplan_id']) ? $apackage['abroadplan_id'] : 0;
                if ($abroadplanId > 0 && !empty($abroadplanInfos[$abroadplanId]['name'])) {
                    $tmp["work_info"]["留学&升学服务计划"] = $abroadplanInfos[$abroadplanId]['name'];
                }
                $tmp['work_info']['备注'] = empty($apackage['remark']) ? "" : $apackage['remark'];
            }else if ($item['type'] == Service_Data_Review::REVIEW_TYPE_APACKAGE_TRANSFER) {
                $reviewExt = json_decode($item["ext"], true);
                if (empty($reviewExt)) {
                    continue;
                }

                $transferType = "新建服务";
                if (isset($reviewExt["transfer_type"]) && $reviewExt["transfer_type"] == 2) {
                    $transferType = "已有服务";
                }
                $originAbroadplan = "-";
                if (!empty($abroadplanInfos[$reviewExt["origin_abroadplan_id"]]['name'])) {
                    $originAbroadplan = $abroadplanInfos[$reviewExt["origin_abroadplan_id"]]['name'];
                }
                $distinAbroadplan = "-";
                if (!empty($abroadplanInfos[$reviewExt["distin_abroadplan_id"]]['name'])) {
                    $distinAbroadplan = $abroadplanInfos[$reviewExt["origin_abroadplan_id"]]['name'];
                }
                $tmp["work_info"] = array(
                    "类型"      => sprintf("计划服务结转(%s)", $transferType),
                    "原留学&升学服务计划" => $originAbroadplan,
                    "原服务ID" => $reviewExt["origin_apackage_id"],
                    "目的留学&升学服务计划" => $distinAbroadplan,
                    "目的服务ID" => $reviewExt["distin_apackage_id"],
                    "结转课时"  => $reviewExt["transfer_schedule_nums"] ."小时",
                    "备注"  => empty($reviewExt["transfer_remark"]) ? "" : $reviewExt["transfer_remark"],
                );
            }
            $result[] = $tmp;
        }

        return $result;
    }
}