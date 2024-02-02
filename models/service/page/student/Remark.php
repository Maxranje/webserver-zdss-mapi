<?php

class Service_Page_Student_Remark extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $uid        = empty($this->request['uid']) ? 0 : intval($this->request['uid']);
        $remark_new = empty($this->request['remark_new']) ? "" : trim($this->request['remark_new']);
        $remark_old = empty($this->request['remark']) ? "" : trim($this->request['remark']);

        if ($uid <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 请求参数错误, 请检查");
        }

        if (empty($remark_new) || strlen($remark_new) > 100) {
            throw new Zy_Core_Exception(405, "操作失败, 新备注不能为空或字数不能超过100字");
        }

        $serviceData = new Service_Data_Profile();
        $userInfo = $serviceData->getUserInfoByUid($uid);
        if (empty($userInfo)) {
            throw new Zy_Core_Exception(405, "操作失败, 无法查到相关用户");
        }

        $ext = empty($userInfo['ext']) ? array() : json_decode($userInfo['ext'], true);
        $ext["remark"] = $remark_new;

        $profile = [
            "ext" => json_encode($ext),
        ];

        $ret = $serviceData->editUserInfo($uid, $profile);
        if ($ret === false) {
            throw new Zy_Core_Exception(405, "更新失败, 请重试");
        }
        return array();
    }
}