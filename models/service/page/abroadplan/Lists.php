<?php

class Service_Page_Abroadplan_Lists extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $pn         = empty($this->request['page']) ? 1 : intval($this->request['page']);
        $rn         = empty($this->request['perPage']) ? 20 : intval($this->request['perPage']);
        $name       = empty($this->request['name']) ? "" : trim($this->request['name']);
        $isSelect   = empty($this->request['is_select']) ? false : true;
        $isPrice    = empty($this->request['is_price']) ? false : true;

        $pn = ($pn-1) * $rn;

        $serviceData = new Service_Data_Abroadplan();

        $conds = array();
        if (!empty($name)) {
            $conds[] = "name like '%".$name."%'";
        }
        $arrAppends = array(
            "order by id desc",
        );
        if (!$isSelect) {
            $arrAppends[] = "limit {$pn} , {$rn}";
        }
        $lists = $serviceData->getListByConds($conds, array(), NULL, $arrAppends);
        if (empty($lists)) {
            return array();
        }
        $lists = $this->formatDefault($lists, $isSelect);

        if ($isSelect) {
            return $this->formatSelect($lists, $isPrice);
        } 

        $total = $serviceData->getTotalByConds($conds);
        return array(
            'rows' => $lists,
            'total' => $total,
        );      
    }
    
    public function formatDefault ($lists, $isSelect) {

        $abroadplanIds = Zy_Helper_Utils::arrayInt($lists, "id");
        $operatorIds = Zy_Helper_Utils::arrayInt($lists, "operator");

        // confrim
        $confirmInfos = array();
        $operatorInfos = array();
        if (!$isSelect) {
            $serviceData = new Service_Data_Abroadplanconfirm();
            $confirmInfos = $serviceData->getConfirmByIds($abroadplanIds);
            $confirmInfos = array_column($confirmInfos, null, "abroadplan_id");

            // user profile
            $serviceUser = new Service_Data_Profile();
            $operatorInfos = $serviceUser->getUserInfoByUids($operatorIds);
            $operatorInfos = array_column($operatorInfos, null , "uid");            
        }

        foreach ($lists as $key => $value) {
            $value['create_time']   = date("Y-m-d H:i:s", $value['create_time']);
            $value['update_time']   = date("Y-m-d H:i:s", $value['update_time']);
            $value['price']         = sprintf("%.2f", $value['price'] / 100);
            $value["operator"]      = empty($operatorInfos[$value['operator']]["nickname"]) ? "-" : $operatorInfos[$value['operator']]["nickname"];
            $value["confirm"]       = array();
            if (!empty($confirmInfos[$value["id"]]['content'])) {
                $value["confirm"] = $confirmInfos[$value["id"]]['content'];
            }
            $lists[$key] = $value;
        }
        return $lists;
    }

    // Select格式化数据
    private function formatSelect($lists, $isPrice) {
        if (empty($lists)) {
            return array();
        }

        $options = array();
        foreach ($lists as $item) {
            $tmp = array(
                'label' => sprintf("%s 【%s小时】", $item['name'], $item['duration']),
                'value' => $item['id'],
            );
            if ($isPrice) {
                $tmp["label"] = sprintf("%s 【%s元】", $tmp["label"], $item["price"]);
                $tmp["value"] = sprintf("%d-%s", $item["id"], $item["price"]);
            }
            $options[] = $tmp;
        }
        return array('options' => array_values($options));
    }
}   