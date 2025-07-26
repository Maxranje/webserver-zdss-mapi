<?php

class Service_Page_Napi_Abroadplan_Summary extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkStudent()) {
            throw new Zy_Core_Exception(405, "无权限");
        }        
        $result = array(
            'total_services'      => 0,
            'completed_services'  => 0,
            'progress_services'   => 0,
            'todo_items'          => array(),
        );

        $serviceData = new Service_Data_Aporderpackage();
        $apackageList = $serviceData->getApackagesByUid($this->adption["userid"]);
        if (empty($apackageList)) {
            return array();
        }

        $ableLists = array();
        foreach ($apackageList as $item) {
            if (in_array($item["state"], [
                Service_Data_Aporderpackage::APORDER_STATUS_ABLE,
                Service_Data_Aporderpackage::APORDER_STATUS_ADDDUR_PEND,
            ])) {
                $result["progress_services"]++;
                $ableLists[] = intval($item["id"]);
            } else if (in_array($item["state"], [
                Service_Data_Aporderpackage::APORDER_STATUS_DONE,
                Service_Data_Aporderpackage::APORDER_STATUS_TRANS_REFUES,
            ])) {
                $result["completed_services"]++;
            } else {
                continue;
            }
            $result["total_services"]++;
        }

        $todoList = array();
        if (!empty($ableLists)) {
            $serviceData = new Service_Data_Apackageconfirm();
            $confirmList = $serviceData->getConfirmByIds($ableLists);
            if (!empty($confirmList)) {
                foreach ($confirmList as $v) {
                    if (empty($v["content"])) {
                        continue;
                    }
                    foreach ($v["content"] as $vv) {
                        if (!empty($vv["items"])) { 
                            foreach ($vv["items"] as $vvv) {
                                if (!empty($vvv["is_oc"]) && empty($vvv["is_sc"])) {
                                    if (!isset($todoList[$v["abroadplan_id"]])) {
                                        $todoList[$v["abroadplan_id"]] = 0;
                                    }
                                    $todoList[$v["abroadplan_id"]]++;
                                }
                            }
                        }
                    }
                }
            }

            if (!empty($todoList)) {
                $ids = Zy_Helper_Utils::arrayInt(array_keys($todoList));
                $serviceData = new Service_Data_Abroadplan();
                $abroadplanInfos = $serviceData->getAbroadplanByIds($ids);
                if (!empty($abroadplanInfos)) {
                    foreach ($abroadplanInfos as $v) {
                        if (!empty($v["name"]) && !empty($todoList[$v["id"]])) {
                            $result["todo_items"][] = array(
                                "service_name" => $v["name"],
                                "pending_count" => $todoList[$v["id"]],
                            );
                        }
                    }
                }
            }
        }
        return $result;
    }
}