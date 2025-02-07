<?php

class Service_Page_Abroadorder_Describe extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $orderId = empty($this->request['order_id']) ? 0 : intval($this->request['order_id']);
        if ($orderId <= 0) {
            return array();
        }

        // 获取订单信息
        $serviceData = new Service_Data_Order();
        $orderData = $serviceData->getAporderById($orderId);
        if (empty($orderData)) {
            throw new Zy_Core_Exception(405, "操作失败, 订单信息获取失败");
        }
        $orderData['ext'] = json_decode($orderData["ext"], true);
        if (empty($orderData['ext']["schedule_nums"])) {
            throw new Zy_Core_Exception(405, "操作失败, 订单信息不正确, 请重试");
        }

        // 获取uid信息
        $serviceUser = new Service_Data_Profile();
        $studentInfo = $serviceUser->getUserInfoByUid(intval($orderData["student_uid"]));
        if (empty($studentInfo)) {
            throw new Zy_Core_Exception(405, "操作失败, 订单关联的学员不存在经检查");
        }
        if (!$this->isOperator(Service_Data_Roles::ROLE_MODE_STUDENT_AMOUNT_HANDLE, $studentInfo['sop_uid'])) {
            throw new Zy_Core_Exception(405, "操作失败, 非学管或特定权限人员, 无权限查看");
        }

        $serviceData = new Service_Data_Curriculum();
        $conds = array(
            "order_id" => $orderId,
            "state" => Service_Data_Schedule::SCHEDULE_DONE,
        );
        $currList = $serviceData->getListByConds($conds);
        $currList = array_column($currList, null , "schedule_id");

        // 课时变更记录
        $serviceData = new Service_Data_Orderchange ();
        $conds = array(
            "order_id" => $orderId,
            "type" => Service_Data_Orderchange::CHANGE_APORDER_ORDER_CHANGE,
        );
        $changeList = $serviceData->getListByConds($conds);

        // 获取结算记录
        $serviceData = new Service_Data_Records();
        $conds = array(
            "order_id" => $orderId,
            "uid" => intval($orderData["student_uid"]),
            "category" => Service_Data_Schedule::CATEGORY_STUDENT_PAID, // 以学员时间为准
        );
        $recordList = $serviceData->getListByConds($conds);            

        // 结算记录和结转记录都没有则退出
        if (empty($recordList) && empty($changeList)) {
            return array();
        }

        // 结转和订单信息要耦合在一起
        $resultList = array_merge($recordList, $changeList);
        usort($resultList, function($a, $b) {
            return $a["create_time"] - $b["create_time"];
        });

        $operatorids = array_column($resultList, "operator");
        $operatorInfos = $serviceUser->getUserInfoByUids($operatorids);
        $operatorInfos = array_column($operatorInfos, null, "uid");

        $orderScheduleNums = 0;

        // 格式化
        $result = array();
        foreach ($resultList as $item) {
            $isChange = empty($item["schedule_id"]) ;
            $tmp = array();
            $tmp["modify_time"] = "-";
            $tmp["modify_type"] = "-";
            $tmp["schedule_time"] = "-";
            $tmp["duration"] = "-";
            $tmp["check_balance"] = "-";
            $tmp["transfer_balance"] = "-";
            $tmp["last_balance"] = "-";
            $tmp["operatorinfo"] = "-";
            $tmp["transfer_duration"] = "-";
            $tmp["last_duration"] = "-";

            if ($isChange) {
                $changeOrderInfo = empty($item["order_info"]) ? array() : json_decode($item["order_info"], true);
                if (!isset($changeOrderInfo["action_type"]) || $changeOrderInfo["action_type"] == "delete") {
                    continue;
                }
                $orderScheduleNums      += intval($changeOrderInfo['schedule_nums']);

                $tmp["modify_time"]     = date("Y-m-d H:i:s", $item["create_time"]);
                $tmp["modify_type"]     = 1;
                $tmp["change_duration"] = empty($changeOrderInfo["schedule_nums"]) ? 0 :$changeOrderInfo["schedule_nums"];
                $tmp["last_duration"]   = $orderScheduleNums;
                $tmp["operatorinfo"]    = empty($operatorInfos[$item["operator"]]["nickname"]) ? "" : $operatorInfos[$item["operator"]]["nickname"];                
            } else {
                $scheduleId = $item["schedule_id"];
                $scheduleDetail = empty($currList[$scheduleId]) ? array() : $currList[$scheduleId];
                if (!empty($scheduleDetail)) {
                    $tmp["schedule_time"] = sprintf("%s~%s", date("Y-m-d H:i", $scheduleDetail["start_time"]), date("H:i", $scheduleDetail["end_time"]));
                    $tmp["duration"] = sprintf("%.2f", ($scheduleDetail["end_time"] - $scheduleDetail["start_time"])/3600);
                    if ($item["state"] == 2) {
                        $tmp["modify_type"] = 3;
                        $orderScheduleNums += $tmp["duration"];
                    } else if ($item["state"] == 1) {
                        $tmp["modify_type"] = 2;
                        $orderScheduleNums -= $tmp["duration"];
                    }
                }
                $tmp["modify_time"] = date("Y-m-d H:i:s", $item["create_time"]);
                $tmp["last_duration"] = $orderScheduleNums;
                $tmp["operatorinfo"] = empty($operatorInfos[$item["operator"]]["nickname"]) ? "" : $operatorInfos[$item["operator"]]["nickname"];                
            }
            $result[] = $tmp;
        }   

        return array(
            'rows' => $result,
            'total' => count($result),
        );
    }
}