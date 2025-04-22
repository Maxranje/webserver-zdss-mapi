<?php

class Service_Page_Abroadorder_Confirm_Upload extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $key        = empty($this->request["key"]) ? "" : trim($this->request['key']);
        $apackageId = empty($this->request["apackage_id"]) ? 0 : intval($this->request["apackage_id"]);
        if (empty($key) || $apackageId <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 请求参数错误!");
        }

        if (empty($_FILES["file"]["tmp_name"])) {
            throw new Exception("操作失败, 未上传文件~") ;
        }

        // 先找服务
        $serviceData = new Service_Data_Aporderpackage();
        $apackageInfo = $serviceData->getAbroadpackageById($apackageId);
        if (empty($apackageInfo)) {
            throw new Zy_Core_Exception(405, "操作失败, 服务不存在, 无法操作");
        }
        if (!in_array($apackageInfo["state"], Service_Data_Aporderpackage::APORDER_STATUS_ABLE_MAP)) {
            throw new Zy_Core_Exception(405, "操作失败, 服务不再有效状态内不能进行操作, 请确认服务状态");
        }

        // 找check单项
        $serviceData = new Service_Data_Apackageconfirm();
        $confirm= $serviceData->getConfirmById(intval($apackageInfo["id"]));
        if (empty($confirm["content"])) {
            throw new Zy_Core_Exception(405, "操作失败, 检查项获取失败或未配置");
        }      
        
        $content = $confirm["content"];
        $confirmActiveItem = false;
        foreach ($content as &$v) {
            foreach ($v["items"] as &$vv) {
                if (!empty($vv["key"]) && $vv["key"] == $key)  {
                    $confirmActiveItem = &$vv;
                    break;
                }
            }
        }

        // 没有找到
        if ($confirmActiveItem === false) { 
            throw new Zy_Core_Exception(405, "操作失败, 检查项单项不存在");
        }
        
        $uploadPath = Zy_Helper_Config::getConfig('config')['upload_path'];
        $ret = Zy_Helper_Upload::saveUploadedConfirmFile("file", $uploadPath, $key);
        if (empty($ret)) {
            throw new Zy_Core_Exception(405, "操作失败, 文件上传失败, 请重新尝试");
        }

        $confirmActiveItem["up_ext"] = $ret;
        $profile = [
            "content"       => json_encode($content), 
            "update_time"   => time() , 
        ];
        $ret = $serviceData->update(intval($confirm['id']), $profile);
        if ($ret == false) {
            throw new Zy_Core_Exception(405, "上传失败, 请重新操作");
        }
        return array();
    }

}