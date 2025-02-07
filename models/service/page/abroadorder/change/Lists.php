<?php

class Service_Page_Abroadorder_Change_Lists extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $pn             = empty($this->request['page']) ? 1 : intval($this->request['page']);
        $rn             = empty($this->request['perPage']) ? 20 : intval($this->request['perPage']);
        $orderId        = empty($this->request['order_id']) ? 0 : intval($this->request['order_id']);
        $studentUid     = empty($this->request['student_uid']) ? 0 : intval($this->request['student_uid']);
        $dataRange      = empty($this->request['date_range']) ? "" : $this->request['date_range'];
        $pn             = ($pn-1) * $rn;
        list($sts, $ets) = empty($dataRange) ? array(0,0) : explode(",", $dataRange);

        $conds = array(
            sprintf("type in (%s)", implode(",", Service_Data_Orderchange::$changeAporderMap)),
        );

        if ($orderId > 0) {
            $conds['order_id'] = $orderId;
        }

        if ($studentUid > 0) {
            $conds['student_uid'] = $studentUid;
        }

        if ($sts > 0) {
            $conds[] = "update_time >= ".$sts;
        }
        if ($ets > 0) {
            $conds[] = "update_time <= ".($ets + 1);
        }

        $arrAppends = array(
            'order by id desc',
            "limit {$pn} , {$rn}"
        );
        
        $serviceData = new Service_Data_Orderchange();
        $lists = $serviceData->getListByConds($conds, array(), NULL, $arrAppends);
        if (empty($lists)) {
            return array();
        }
        
        $lists = $this->formatDefault($lists);
        $total = $serviceData->getTotalByConds($conds);
        return array(
            'rows' => $lists,
            'total' => $total,
        );
    }

    // 默认格式化
    private function formatDefault ($lists) {

        $studentUids = Zy_Helper_Utils::arrayInt($lists, 'student_uid');
        $operator  = Zy_Helper_Utils::arrayInt($lists, 'operator');
        
        $uids = array_unique(array_merge($studentUids, $operator));

        $serviceData = new Service_Data_Profile();
        $userInfos = $serviceData->getListByConds(array(sprintf("uid in (%s)", implode(",", $uids))));
        $userInfos = array_column($userInfos, null, 'uid');

        $result = array();
        foreach ($lists as $v) {
            if (empty($userInfos[$v['student_uid']]['nickname'])) {
                continue;
            }
            
            $orderExt   = empty($v['ext']) ? array() : json_decode($v['ext'], true);
            $orderInfo  = empty($v['order_info']) ? array() : json_decode($v["order_info"], true);

            $v['student_name']      = $userInfos[$v['student_uid']]['nickname'];
            $v['operator']          = empty($userInfos[$v['operator']]['nickname']) ? "" :$userInfos[$v['operator']]['nickname'];
            $v['balance']           = sprintf("%.2f", intval($v['balance']) / 100);
            $v['duration']          = floatval(sprintf("%.2f", $v['duration']));
            $v['update_time']       = date("Y年m月d日",$v['update_time']);
            $v['create_time']       = date("Y年m月d日",$v['create_time']);
            $v['remark']            = empty($orderExt['remark']) ? "" : $orderExt['remark'];
            $v["order_id"]          = empty($v['order_id']) ? "-" : $v['order_id'];
            $v["review_state"]      = "-";
            $v["change_state"]      = "-";

            unset($v['ext']);
            unset($v['order_info']);

            // 计划订单特殊处理
            
            if ($v['type'] == Service_Data_Orderchange::CHANGE_APORDER_PACKAGE_CREATE) 
            {
                $v["abroadplan_name"] = empty($orderInfo["abroadplan_name"]) ? "" :$orderInfo["abroadplan_name"];
                $v["review_state"] = $orderInfo["review_state"] == Service_Data_Review::REVIEW_SUC ? "通过" : "拒绝";
            } 
            else if ($v['type'] == Service_Data_Orderchange::CHANGE_APORDER_PACKAGE_DELETE) 
            {
                $v["abroadplan_name"] = empty($orderInfo["abroadplan_name"]) ? "" :$orderInfo["abroadplan_name"];
                $v['duration'] = "-";
            } 
            else if ($v['type'] == Service_Data_Orderchange::CHANGE_APORDER_DURATION_ADD) 
            {
                $v["abroadplan_name"] = empty($orderInfo["abroadplan_name"]) ? "" :$orderInfo["abroadplan_name"];
                $v["review_state"] = $orderInfo["review_state"] == Service_Data_Review::REVIEW_SUC ? "通过" : "拒绝";
                $v["review_state"] = sprintf("%s(变更前课时%s/变更后课时%s)", $v["review_state"], $orderInfo["old_schedule_nums"], $orderInfo["new_schedule_nums"]);
            }
            else if ($v['type'] == Service_Data_Orderchange::CHANGE_APORDER_PACKAGE_OVER) 
            {
                $v["abroadplan_name"] = empty($orderInfo["abroadplan_name"]) ? "" :$orderInfo["abroadplan_name"];
                $v["review_state"] = $orderInfo["review_state"] == Service_Data_Review::REVIEW_SUC ? "通过" : "拒绝";
                $v['duration'] = "-";
            } 
            else if ($v['type'] == Service_Data_Orderchange::CHANGE_APORDER_ORDER_CHANGE) 
            {
                $v["abroadplan_name"] = empty($orderInfo["abroadplan_name"]) ? "" :$orderInfo["abroadplan_name"];
                $v["change_state"] = empty($orderInfo["action_type"]) ? "" :$orderInfo["action_type"];;
                $v['duration'] = "-";
                if ($v["change_state"] == "update" || $v["change_state"] == "create")  {
                    $v["duration"] = empty($orderInfo["schedule_nums"]) ? "" :$orderInfo["schedule_nums"];
                }
                if ($v["change_state"] == "create") {
                    $v["change_state"] = "创建订单";
                } else if ($v["change_state"] == "update") {
                    $v["change_state"] = "调整订单课时";
                } else if ($v["change_state"] == "delete") {
                    $v["change_state"] = "删除订单";
                } 
            }
            else if ($v['type'] == Service_Data_Orderchange::CHANGE_APORDER_PACKAGE_TRANS) 
            {
                $v["abroadplan_name"] = empty($orderInfo["abroadplan_name"]) ? "" :$orderInfo["abroadplan_name"];
                $v["review_state"] = $orderInfo["review_state"] == Service_Data_Review::REVIEW_SUC ? "通过" : "拒绝";
                $v["review_state"] = sprintf("%s(原服务ID:%s/目标服务ID:%s)", $v["review_state"], $orderInfo["origin_apackage_id"], $orderInfo["distin_apackage_id"]);
            }            

            $result[] = $v;
        }
        return $result;
    }
}