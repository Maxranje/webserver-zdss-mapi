<?php

class Service_Page_Napi_Abroadplan_Down extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkStudent()) {
            throw new Zy_Core_Exception(405, "无权限");
        }

        $uid = $this->adption["userid"];

        $id     = empty($this->request["apackage_id"]) ? 0 : intval($this->request['apackage_id']);
        $key    = empty($this->request["key"]) ? "" : trim($this->request['key']);
        if (empty($key) || $id <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 参数异常!");
        }

        // get abroadpackage
        $servicePackage = new Service_Data_Aporderpackage();
        $apackageInfo = $servicePackage->getAbroadpackageById($id);
        if (empty($apackageInfo["uid"]) || $apackageInfo["uid"] != $uid) {
            throw new Zy_Core_Exception(405, "操作失败, 留学服务不存在!");
        }
        
        // 找check单项
        $serviceConfirm = new Service_Data_Apackageconfirm();
        $confirmData = $serviceConfirm->getConfirmById($id);
        if (empty($confirmData["content"])) {
            throw new Zy_Core_Exception(405, "操作失败, 检查项不存在!");
        }

        $confirmActiveItem = false;
        foreach ($confirmData["content"] as &$v) {
            foreach ($v["items"] as &$vv) {
                if (!empty($vv["key"]) && "sc_" . $vv["key"] == $key)  {
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

        $downloadPath = Zy_Helper_Config::getConfig('config')['upload_path'] . "/abroadplan_confrim";
        $downloadPath = sprintf("%s/%s", $downloadPath, $confirmActiveItem["key"] . "." . $confirmActiveItem["up_ext"]);

        try {
            Zy_Helper_Download::normal($downloadPath);
        }catch (Exception $e) {
            throw new Zy_Core_Exception(405, $e->getMessage());
        }
        
        exit;
    }

}