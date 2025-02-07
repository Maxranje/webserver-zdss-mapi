<?php

class Service_Page_Abroadorder_Package_Lists extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $pn             = empty($this->request['page']) ? 1 : intval($this->request['page']);
        $rn             = empty($this->request['perPage']) ? 20 : intval($this->request['perPage']);
        $orderId        = empty($this->request['order_id']) ? 0 : intval($this->request['order_id']);
        $abroadplanId   = empty($this->request['abroadplan_id']) ? 0 : intval($this->request['abroadplan_id']);
        $filterId       = empty($this->request['filter_id']) ? 0 : intval($this->request['filter_id']);
        $studentUid     = empty($this->request['student_uid']) ? 0 : intval($this->request['student_uid']);
        $state          = empty($this->request['state']) ? 0 : intval($this->request['state']);
        $isSelect       = empty($this->request['is_select']) ? false : true;
        $pn             = ($pn-1) * $rn;

        $conds = array();

        if ($orderId > 0) {
            $serviceOrder = new Service_Data_Order();
            $orderData = $serviceOrder->getAporderById($orderId);
            if (empty($orderData)) {
                return array();
            }
            $conds["id"] = intval($orderData['apackage_id']);
        }

        if ($studentUid > 0) {
            $conds['uid'] = $studentUid;
        }

        if ($abroadplanId > 0) {
            $conds['abroadplan_id'] = $abroadplanId;
        }

        if ($state > 0) {
            $conds['state'] = $state;
        }

        $arrAppends[] = 'order by id desc';
        if(!$isSelect) {
            $arrAppends[] = "limit {$pn} , {$rn}";
        }
        
        $serviceData = new Service_Data_Aporderpackage();
        $lists = $serviceData->getListByConds($conds, array(), NULL, $arrAppends);
        if (empty($lists)) {
            return array();
        }
        
        $lists = $this->formatDefault($lists, $isSelect);
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
    private function formatDefault ($lists, $isSelect) {
        $apackageIds    = Zy_Helper_Utils::arrayInt($lists, 'id');
        $studentUids    = Zy_Helper_Utils::arrayInt($lists, 'uid');
        $operatorIds    = Zy_Helper_Utils::arrayInt($lists, 'operator');
        $abroadplanIds  = Zy_Helper_Utils::arrayInt($lists, "abroadplan_id");

        $uids = array_unique(array_merge($studentUids, $operatorIds));

        // 计划
        $serviceData = new Service_Data_Abroadplan();
        $abroadplanInfos = $serviceData->getAbroadplanByIds($abroadplanIds);
        $abroadplanInfos = array_column($abroadplanInfos, null, 'id');

        // 学员
        $serviceData = new Service_Data_Profile();
        $userInfos = $serviceData->getListByConds(array(sprintf("uid in (%s)", implode(",", $uids))));
        $userInfos = array_column($userInfos, null, 'uid');

        // 生源地
        $serviceData = new Service_Data_Birthplace();
        $birthplaces = $serviceData->getListByConds(array("id>0"));
        $birthplaces = array_column($birthplaces, null, 'id');        

        // 订单
        $confirmData = array();
        $apackageOrder = array();
        if(!$isSelect) {
            $serviceData = new Service_Data_Order();
            $orderDatas = $serviceData->getAporderByPackageIds($apackageIds);
            $orderDatas = array_column($orderDatas, null, 'order_id');     
            $orderIds  = Zy_Helper_Utils::arrayInt($orderDatas, "order_id");
    
            // 排课数
            $serviceData = new Service_Data_Curriculum();
            $orderCounts = $serviceData->getScheduleTimeCountByOrder($orderIds);

            $serviceData = new Service_Data_Apackageconfirm();
            $confirmData = $serviceData->getConfirmByIds($apackageIds);
            $confirmData = array_column($confirmData, null, "apackage_id");      
            
            // 订单格式化信息
            $apackageOrder = $this->formatPackageOrder($orderDatas, $orderCounts, $birthplaces);
        }

        // 输出
        $result = array();
        foreach ($lists as $v) {
            if (empty($userInfos[$v['uid']]['nickname'])) {
                continue;
            }
            if (empty($abroadplanInfos[$v["abroadplan_id"]]["name"])) {
                continue;
            }

            // 是否有权限看一些资金相关
            $isModeShowAmount = $this->isOperator(
                Service_Data_Roles::ROLE_MODE_STUDENT_AMOUNT_HANDLE, 
                intval($userInfos[$v['uid']]['sop_uid']));
            
            $extra = json_decode($v['ext'], true);

            $item = array();
            $item['apackage_id']            = intval($v["id"]);
            $item["abroadplan_id"]          = intval($v["abroadplan_id"]);
            $item["abroadplan_name"]        = $abroadplanInfos[$v["abroadplan_id"]]["name"];
            $item["abroadplan_duration"]    = floatval($abroadplanInfos[$v["abroadplan_id"]]["duration"]);
            $item['student_uid']            = intval($v['uid']);
            $item['student_name']           = $userInfos[$v['uid']]['nickname'];
            $item['operator_name']          = empty($userInfos[$v['operator']]['nickname']) ? "" : $userInfos[$v['operator']]['nickname'];
            $item['update_time']            = date("Y年m月d日 H:i",$v['update_time']);
            $item['create_time']            = date("Y年m月d日 H:i",$v['create_time']);
            $item['origin_balance']         = sprintf("%.2f", $extra['origin_balance'] / 100);
            $item['real_balance']           = sprintf("%.2f", $v['price'] / 100);
            $item['schedule_nums']          = floatval($v['schedule_nums']);
            $item["schedule_nums_more"]     = $item['schedule_nums'] - $item["abroadplan_duration"];
            $item["apackage_state"]         = intval($v["state"]);
            $item['remark']                 = $v["remark"];
            $item["is_amount"]              = $isModeShowAmount ? 1 : 0;
            $item['confirm_data']           = $this->getConfirm($v['id'], $confirmData); // 检查项
            $item["transfer_apackage_id"]   = isset($extra["transfer_id"]) ? intval($extra["transfer_id"]) : 0;
            $item["from_transfer_apackage_id"]   = isset($extra["from_transfer_id"]) ? intval($extra["from_transfer_id"]) : 0;

            // 订单列表
            $item["order_lists"] = array();
            if (!empty($apackageOrder['lists'][$v['id']])) {
                $item["order_lists"] = $apackageOrder['lists'][$v['id']];
            }

            // 结算用的功能
            $item["apackage_order_ungive"] = $item["apackage_order_uncheck"] = $item["apackage_order_unband"] = 0;
            if (!empty($apackageOrder['count'][$v["id"]])) {
                $item["apackage_order_ungive"] = $v['schedule_nums'] - $apackageOrder['count'][$v["id"]]['a'];
                $item["apackage_order_uncheck"] = $apackageOrder['count'][$v["id"]]['u'];
                $item["apackage_order_unband"] = $apackageOrder['count'][$v["id"]]['ub'];
            }

            // 优惠信息
            $item['discount_info'] = "";
            if (!empty($extra['discount_z'])) {
                $item['discount_info'] .= ($extra['discount_z']/10) . "折";
            } 
            if (!empty($extra['discount_j'])) {
                $item['discount_info'] .= !empty($item['discount_info']) ? "&" :"";
                $item['discount_info'] .= sprintf("减%.2f元", $extra['discount_j'] / 100);
            }
            if (empty($item["discount_info"])) {
                $item["discount_info"] = "无优惠";
            }
            
            // 状态
            $item["pic_name"] = "";
            if (in_array($v["state"], [
                    Service_Data_Aporderpackage::APORDER_STATUS_ADDDUR_PEND,
                    Service_Data_Aporderpackage::APORDER_STATUS_ABLE_PEND,
                    Service_Data_Aporderpackage::APORDER_STATUS_DONE_PEND,
                    Service_Data_Aporderpackage::APORDER_STATUS_TRANS_PEND,
            ])) {
                $item["pic_name"] = "审核中";
            } else if ($v['state'] == Service_Data_Aporderpackage::APORDER_STATUS_DONE) {
                $item["pic_name"] = "完结";
            }  else if ($v['state'] == Service_Data_Aporderpackage::APORDER_STATUS_TRANS) {
                $item["pic_name"] = "结转完";
            }  else if ($v['state'] == Service_Data_Aporderpackage::APORDER_STATUS_TRANS_REFUES) {
                $item["pic_name"] = "结转拒绝";
            } else if ($v['state'] == Service_Data_Aporderpackage::APORDER_STATUS_ABLE_REFUES) {
                $item["pic_name"] = "拒绝";
            }

            // 没权限看, 需要展示*
            if (!$isModeShowAmount) {
                $item['discount_info'] = "***";
                $item['schedule_nums'] = "***";
                $item['origin_balance'] = "***";
                $item['real_balance'] = "***";
                $item['remark'] = "***";
                $item["apackage_order_ungive"] = "***";
                $item["apackage_order_uncheck"] = "***";
                $item["apackage_order_unband"] = "***";
                // 绑定
                if (!empty($item["order_lists"])) {
                    foreach ($item["order_lists"] as &$aom) {   
                        $aom["has_duration"] = "***";
                        $aom["band_duration"] = "***";
                        $aom["check_duration"] = "***";
                        $aom["uncheck_duration"] = "***";
                    }
                }
            }

            $result[] = $item;
        }
        return $result;
    }

    // 结构化绑定信息
    private function formatPackageOrder ($orderDatas, $orderCounts, $birthplaces) {
        if (empty($orderDatas)) {
            return array();
        }

        // 获取课程, 班型
        $subjectIds     = Zy_Helper_Utils::arrayInt($orderDatas, "subject_id");
        $claszeIds      = Zy_Helper_Utils::arrayInt($orderDatas, "cid");

        $serviceData    = new Service_Data_Subject();
        $subjectInfos   = $serviceData->getSubjectByIds($subjectIds);
        $subjectInfos   = array_column($subjectInfos, null, "id");

        $serviceData    = new Service_Data_Clasze();
        $claszeInfos    = $serviceData->getClaszeByIds($claszeIds);
        $claszeInfos    = array_column($claszeInfos, null, "id");             

        
        $lists = array();
        $count = array();
        foreach ($orderDatas as $v) {
            $orderId = intval($v["order_id"]);
            $apackageId = intval($v["apackage_id"]);
            $orderExt = json_decode($v["ext"], true);
            // 优先获取数据, 进入到结转列表
            $band = empty($orderCounts[$orderId]) ? array() : $orderCounts[$orderId];
            if (!empty($band)) {
                if (!isset($count[$apackageId])) {
                    $count[$apackageId] = array("a" => 0, "c" => 0, "u" => 0, "ub"=>0);
                }
                $count[$apackageId]["a"] += $band["a"];
                $count[$apackageId]["c"] += $band["c"];
                $count[$apackageId]["u"] += $band["u"];
                $count[$apackageId]["ub"] += $orderExt["schedule_nums"] - $band["a"];
            }

            // 渲染数据
            if (empty($subjectInfos[$v['subject_id']]['name'])) {
                continue;
            }
            if (empty($claszeInfos[$v['cid']]['name'])) {
                continue;
            }
            if (empty($birthplaces[$v['bpid']]['name'])) {
                continue;
            }
            if (!isset($lists[$apackageId])) {
                $lists[$apackageId] = array();
            }

            $lists[$apackageId][] = array(
                "order_id"          => $orderId,
                "subject_name"      => $subjectInfos[$v['subject_id']]['name'],
                "clasze_name"       => $claszeInfos[$v['cid']]['name'],
                "birthplace_name"   => $birthplaces[$v['bpid']]["name"],
                "has_duration"      => $orderExt["schedule_nums"],
                "band_duration"     => empty($band['a']) ? 0 : $band['a'],
                "check_duration"    => empty($band['c']) ? 0 : $band['c'],
                "uncheck_duration"  => empty($band['u']) ? 0 : $band['u'],
                "progress"          => empty($band['c']) || empty($band['a']) ? 0 : intval($band['c'] / $band['a'] * 100),
            );
        }
        return array("lists" => $lists, "count" => $count);
    }

    private function getConfirm ($id, $confirmData) {
        if (empty($confirmData[$id]['content'])) {
            return array();
        }
        $confirmData = $confirmData[$id]['content'];

        $result = array();
        foreach ($confirmData as $v) {
            $all = count($v['items']);
            $oc = $sc = 0;
            foreach ($v["items"] as $vv) {
                if (!empty($vv['is_oc'])) {
                    $oc++;
                }
                if (!empty($vv["is_sc"])) {
                    $sc++;
                }
            }
            $result[] = sprintf("%s - 共%d项 (%d/%d)", $v["title"], $all, $oc, $sc);
        }
        return $result;
    }    

    private function formatSelect ($lists, $filterId = 0) {
        if (empty($lists)) {
            return array();
        }

        $options = array();
        foreach ($lists as $item) {
            if ($item["apackage_state"] != Service_Data_Aporderpackage::APORDER_STATUS_ABLE) {
                continue;
            }
            if ($filterId > 0 && $item["apackage_id"] == $filterId) {
                continue;
            }
            $options[] = array(
                'label' => sprintf("%s - %s", $item['student_name'], $item['abroadplan_name']),
                'value' => intval($item['apackage_id']),
            );
        }
        return array('options' => array_values($options));
    }
}