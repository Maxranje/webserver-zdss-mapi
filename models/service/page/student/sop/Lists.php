<?php

class Service_Page_Student_Sop_Lists extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $sopuid = empty($this->request['sop_uid']) ? 0 : intval($this->request['sop_uid']);
        $chartsType = empty($this->request['chart_type']) ? "pie" : trim($this->request['chart_type']);
        $orderBy = empty($this->request['orderBy']) ? "" : trim($this->request['orderBy']);
        $orderDir = empty($this->request['orderDir']) ? "desc" : trim($this->request['orderDir']);
        $isCharts = empty($this->request['is_charts']) ? false : true;

        if ($isCharts) {
            if ($chartsType == "pie") {
                return $this->getChartsDataByPie();
            }
            if ($chartsType == "line") {
                return $this->getChartsDataByLine();
            }
            throw new Zy_Core_Exception(405, "操作失败, 无法识别图形类型");
        }

        // 列表
        return $this->getListData($sopuid, $orderBy, $orderDir);
        
    }

    // 格式化数据
    private function getListData($sopUid, $orderBy, $orderDir) {
        $serviceData = new Service_Data_Profile();
        $conds = array();
        if ($sopUid > 0) {
            $conds[] = sprintf("sop_uid = %d", $sopUid);
        } else {
            $conds[] = "sop_uid > 0";
        }
        $fileds = array(
            "count(uid) as count",
            'state',
            "sop_uid",
        );
        $appends = array(
            'group by sop_uid, state',
        );

        $lists = $serviceData->getListByConds($conds, $fileds, null, $appends);
        if (empty($lists)) {
            return array();
        }

        // 获取学管信息
        $sopUids = Zy_Helper_Utils::arrayInt($lists, "sop_uid");
        $sopInfos = $serviceData->getUserInfoByUids($sopUids);
        if (empty($sopInfos)) {
            return array();
        }
        $sopInfos = array_column($sopInfos, null, "uid");        

        $result = array();
        foreach ($lists as $item) {
            if (empty($sopInfos[$item["sop_uid"]]["nickname"])) {
                continue;
            }
            $sopUID = intval($item["sop_uid"]);
            if(!isset($result[$sopUID])) {
                $result[$sopUID] = array(
                    "uid" => $sopUID,
                    "nickname" => $sopInfos[$sopUID]["nickname"],
                    "count" => 0,
                    "x1" => 0,
                    "x2" => 0,
                    "x3" => 0,
                    "x4" => 0,
                );
            }

            $result[$sopUID]["count"] += intval($item["count"]) ;
            if ($item["state"] == 1) {
                $result[$sopUID]['x1'] = intval($item['count']);
            }
            if ($item["state"] == 2) {
                $result[$sopUID]['x2'] = intval($item['count']);
            }
            if ($item["state"] == 3) {
                $result[$sopUID]['x3'] = intval($item['count']);
            }
            if ($item["state"] == 4) {
                $result[$sopUID]['x4'] = intval($item['count']);
            }
        }
        $result = array_values($result);
        if (!empty($orderBy) && !empty($orderDir)) {
            foreach ($result as $key => $row) {
                $field[$key]  = $row[$orderBy];
            }
            $sortKey = $orderDir == "desc" ? SORT_DESC : SORT_ASC;
            array_multisort($field, $sortKey, $result); // 假设按’field’字段升序排序
        }

        return array(
            'rows' => $result,
            'total' => count($result),
        );
    }

    // 饼状图
    private function getChartsDataByPie() {
        $serviceData = new Service_Data_Profile();
        $conds = array(
            "sop_uid > 0",
        );
        $fileds = array(
            "count(uid) as count",
            "sop_uid"
        );
        $appends = array(
            "group by sop_uid"
        );
        $lists = $serviceData->getListByConds($conds, $fileds, null, $appends);
        if (empty($lists)) {
            return array();
        }

        $sopUids = Zy_Helper_Utils::arrayInt($lists, "sop_uid");
        $sopInfos = $serviceData->getUserInfoByUids($sopUids);
        if (empty($sopInfos)) {
            return array();
        }
        $sopInfos = array_column($sopInfos, null, "uid");

        $result = array("charts" => array());
        foreach ($lists as $item) {
            if ($item["count"] <= 0 || empty($sopInfos[$item["sop_uid"]]["nickname"])) {
                continue;
            }
            $result["charts"][] = array("value" => $item["count"], "name" => $sopInfos[$item["sop_uid"]]["nickname"]);
        }
        return $result;
    }

        // 饼状图
    private function getChartsDataByLine() {
        $serviceData = new Service_Data_Profile();
        $conds = array(
            "sop_uid > 0",
        );
        $fileds = array(
            "count(uid) as count",
            "sop_uid",
            "state"
        );
        $appends = array(
            "group by sop_uid, state"
        );
        $lists = $serviceData->getListByConds($conds, $fileds, null, $appends);
        if (empty($lists)) {
            return array();
        }

        $sopUids = Zy_Helper_Utils::arrayInt($lists, "sop_uid");
        $sopInfos = $serviceData->getUserInfoByUids($sopUids);
        if (empty($sopInfos)) {
            return array();
        }
        $sopInfos = array_column($sopInfos, null, "uid");

        $result = array("xAxis" => array(), "stateValue1" => array(), "stateValue2" => array(), "stateValue3" => array(), "stateValue4" => array());
        $result = array();
        foreach ($lists as $item) {
            if ($item["count"] <= 0 || empty($sopInfos[$item["sop_uid"]]["nickname"])) {
                continue;
            }
            $sopUID = intval($item["sop_uid"]);
            if(!isset($result[$sopUID])) {
                $result[$sopUID] = array(
                    "name" => $sopInfos[$sopUID]["nickname"],
                    "x1" => 0,
                    "x2" => 0,
                    "x3" => 0,
                    "x4" => 0,
                );
            }

            if ($item["state"] == 1) {
                $result[$sopUID]['x1'] = intval($item['count']);
            }
            if ($item["state"] == 2) {
                $result[$sopUID]['x2'] = intval($item['count']);
            }
            if ($item["state"] == 3) {
                $result[$sopUID]['x3'] = intval($item['count']);
            }
            if ($item["state"] == 4) {
                $result[$sopUID]['x4'] = intval($item['count']);
            }
        }

        $output = array("xAxis" => array(), "x1" => array(), "x2" => array(), "x3" => array(), "x4" => array());
        foreach ($result as $item) {
            $output["xAxis"][] = $item["name"];
            $output["x1"][] = $item["x1"];
            $output["x2"][] = $item["x2"];
            $output["x3"][] = $item["x3"];
            $output["x4"][] = $item["x4"];
        }
        return $output;
    }
}