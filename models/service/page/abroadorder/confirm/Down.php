<?php

class Service_Page_Abroadorder_Confirm_Down extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限操作");
        }

        $checkId    = empty($this->request["check_id"]) ? "" : trim($this->request['check_id']);
        $apackageId = empty($this->request["apackage_id"]) ? "" : trim($this->request['apackage_id']);
        $token = empty($this->request["token"]) ? "" : trim($this->request['token']);
        if (empty($checkId) || (empty($apackageId) && empty($token))) {
            throw new Zy_Core_Exception(405, "操作失败, 参数异常!");
        }

        if (!empty($token)) {
            $apackageInfo = $this->getApackageInfo($token);
            $apackageId = intval($apackageInfo["id"]);
        } else {
            $serviceData = new Service_Data_Aporderpackage();
            $apackageInfo = $serviceData->getAbroadpackageById(intval($apackageId));
        }
        if (empty($apackageInfo)) {
            throw new Zy_Core_Exception(405, "操作失败, 服务不存在, 请确认");
        }        

        // 找check单项
        $serviceData = new Service_Data_Apackageconfirm();
        $confirmData = $serviceData->getConfirmById(intval($apackageId));
        if (empty($confirmData["content"])) {
            throw new Zy_Core_Exception(405, "操作失败, 检查项不存在, 请确认");
        }

        $confirmActiveItem = false;
        foreach ($confirmData["content"] as &$v) {
            foreach ($v["items"] as &$vv) {
                if (!empty($vv["key"]) && $vv["key"] == $checkId)  {
                    $confirmActiveItem = $vv;
                    break;
                }
            }
        }

        // 没有找到
        if ($confirmActiveItem === false) { 
            throw new Zy_Core_Exception(405, "操作失败, 检查项不存在");
        }
        if (empty($confirmActiveItem['up_ext'])) {
            throw new Zy_Core_Exception(405, "操作失败, 未配置上传或未上传, 请检查");
        }

        $downloadPath = Zy_Helper_Config::getConfig('config')['upload_path'];
        $downloadPath = sprintf("%s/%s", $downloadPath, $checkId . "." . $confirmActiveItem["up_ext"]);

        try {
            Zy_Helper_Download::normal($downloadPath);
        }catch (Exception $e) {
            throw new Zy_Core_Exception(405, $e->getMessage());
        }
        
        exit;
    }

    // 根据学员uid 和token找到apckage
    private function getApackageInfo ($token) {
        $serviceData = new Service_Data_Aporderpackage();
        $apackageInfos = $serviceData->getListByConds(array("uid" => $this->adption["userid"]));
        if (empty($apackageInfos)) {
            return array();
        }
        foreach ($apackageInfos as $item) {
            if (md5($item["id"]) == $token) {
                return $item;
            }
        }
        return array();
    }

}