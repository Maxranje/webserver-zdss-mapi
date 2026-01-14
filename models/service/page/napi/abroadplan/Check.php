<?php

class Service_Page_Napi_Abroadplan_Check extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkStudent()) {
            throw new Zy_Core_Exception(405, "无权限");
        }
        $key = empty($this->request["key"]) ? "" : trim($this->request['key']);
        $apakcageId = empty($this->request["service_id"]) ? 0 : intval($this->request['service_id']);
        
        $uid = $this->adption["userid"];
        if (empty($key) || $apakcageId <= 0) {
            throw new Zy_Core_Exception(405, "request err");
        }

        $serviceData = new Service_Data_Aporderpackage();
        $apackageInfo = $serviceData->getAbroadpackageById($apakcageId);
        if (empty($apackageInfo) || $apackageInfo["uid"] != $uid) {
            throw new Zy_Core_Exception(405, "操作失败, 服务不存在或不在有效状态内, 不可操作, 请联系学管");
        }
        if (!in_array($apackageInfo['state'], Service_Data_Aporderpackage::APORDER_STATUS_ABLE_MAP)) {
            throw new Zy_Core_Exception(405, "操作失败, 服务不在有效状态内, 不可删除");
        }

        $serviceData = new Service_Data_Apackageconfirm();
        $confirm= $serviceData->getConfirmById($apakcageId);
        if (empty($confirm["content"])) {
            throw new Zy_Core_Exception(405, "操作失败, 检查项不存在, 请联系学管check");
        }      
        
        $content = $confirm["content"];
        $flag = false;
        foreach ($content as &$v) {
            foreach ($v["items"] as &$vv) {
                if (!empty($vv["key"]) && "sc_" . $vv["key"] == $key)  {
                    $vv["is_sc"] = 1;
                    $vv['s_id'] = $uid;
                    $vv['s_time'] = time();
                    $flag = true;
                }
            }
        }

        if ($flag) {
            $profile = [
                "content"       => json_encode($content), 
                "update_time"   => time() , 
            ];
            $ret = $serviceData->update(intval($confirm['id']), $profile);
            if ($ret == false) {
                throw new Zy_Core_Exception(405, "check failed");
            }
        }
        return array();
    }

}