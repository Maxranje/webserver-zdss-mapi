<?php

class Service_Page_Order_Describe extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $orderId = empty($this->request['order_id']) ? 0 : intval($this->request['order_id']);
        if ($orderId <= 0) {
            return array();
        }

        $resultList = $changeList = $recordList = array();

        // 获取订单信息
        $serviceData = new Service_Data_Order();
        $orderInfo = $serviceData->getOrderById($orderId);
        if (empty($orderInfo) || !empty($orderInfo["isfree"])) {
            throw new Zy_Core_Exception(405, "操作失败, 订单信息获取失败, 或订单为免费订单");
        }
        $orderInfo['ext'] = empty($orderInfo['ext']) ? array() : json_decode($orderInfo["ext"], true);

        // 获取uid信息
        $serviceUser = new Service_Data_Profile();
        $studentInfo = $serviceUser->getUserInfoByUid(intval($orderInfo["student_uid"]));
        if (empty($studentInfo)) {
            throw new Zy_Core_Exception(405, "操作失败, 订单关联的学员无法获取信息或已被删除");
        }

        if ($studentInfo['sop_uid'] != OPERATOR && !$this->isModeAble(Service_Data_Roles::ROLE_MODE_STUDENT_AMOUNT_HANDLE)) {
            throw new Zy_Core_Exception(405, "操作失败, 无权限查看");
        }

        $serviceData = new Service_Data_Curriculum();
        $conds = array(
            "order_id" => $orderId,
            "state" => Service_Data_Schedule::SCHEDULE_DONE,
        );
        $currList = $serviceData->getListByConds($conds);
        $currList = array_column($currList, null , "schedule_id");

        // 结转记录
        $serviceChange = new Service_Data_Orderchange ();
        $conds = array(
            "order_id" => $orderId,
            "type" => Service_Data_Orderchange::CHANGE_REFUND,
        );
        $changeList = $serviceChange->getListByConds($conds);

        // 获取结算记录
        $serviceRecords = new Service_Data_Records();
        $conds = array(
            "order_id" => $orderId,
            "uid" => intval($orderInfo["student_uid"]),
            "category" => Service_Data_Schedule::CATEGORY_STUDENT_PAID, // 以学员时间为准
        );
        $recordList = $serviceRecords->getListByConds($conds);            

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

        // 对外输出数据
        $orderBalance = 0;
        if (!empty($orderInfo['ext']["real_balance"])) {
            $orderBalance = $orderInfo['ext']["real_balance"];
        }

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

            if ($isChange) {
                $orderBalance -= $item["balance"];
                $tmp["modify_time"] = date("Y-m-d H:i:s", $item["create_time"]);
                $tmp["modify_type"] = 1;
                $tmp["transfer_balance"] = sprintf("%.2f", $item["balance"] / 100);
                $tmp["transfer_duration"] = sprintf("%.2f", $item["duration"]);
                $tmp["last_balance"] = sprintf("%.2f", $orderBalance/100);
                $tmp["operatorinfo"] = empty($operatorInfos[$item["operator"]]["nickname"]) ? "" : $operatorInfos[$item["operator"]]["nickname"];                
            } else {
                
                $scheduleId = $item["schedule_id"];
                $scheduleDetail = empty($currList[$scheduleId]) ? array() : $currList[$scheduleId];
                if (!empty($scheduleDetail)) {
                    $tmp["schedule_time"] = sprintf("%s~%s", date("Y-m-d H:i", $scheduleDetail["start_time"]), date("H:i", $scheduleDetail["end_time"]));
                    $tmp["duration"] = sprintf("%.2f", ($scheduleDetail["end_time"] - $scheduleDetail["start_time"])/3600);
                }
                $tmp["modify_time"] = date("Y-m-d H:i:s", $item["create_time"]);
                if ($item["state"] == 2) {
                    $orderBalance += $item["money"];
                    $tmp["modify_type"] = 3;
                } else if ($item["state"] == 1) {
                    $orderBalance -= $item["money"];
                    $tmp["modify_type"] = 2;
                }
                $tmp["check_balance"] = sprintf("%.2f", $item["money"]/100);
                $tmp["last_balance"] = sprintf("%.2f", $orderBalance/100);
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