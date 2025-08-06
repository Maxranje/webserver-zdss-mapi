<?php

class Service_Page_Account_SignReset extends Zy_Core_Service{

    public function execute () {
        $username    = empty($this->request['username']) ? "" : trim($this->request['username']);
        $oldPassword = empty($this->request['oldPassword']) ? "" : trim($this->request['oldPassword']);
        $newPassword = empty($this->request['newPassword']) ? "" : trim($this->request['newPassword']);
        if (empty($username) || empty($oldPassword) || empty($newPassword)) {
            throw new Zy_Core_Exception(405, "操作失败, 变更内容不能为空");
        }

        if ($oldPassword == $newPassword) {
            throw new Zy_Core_Exception(405, "操作失败, 重置密码与当前密码一致");
        }

        if (!Zy_Helper_Utils::checkStrictStr($username, 1) || !Zy_Helper_Utils::checkStr($oldPassword) || !Zy_Helper_Utils::checkStr($newPassword)) {
            throw new Zy_Core_Exception(405, "操作失败, 内容必须是英文,数字或逗号, 6-20位之间");
        }

        $serviceData = new Service_Data_Profile();
        $userInfo = $serviceData->getUserInfoByNameAndPass($username, $oldPassword);
        if (empty($userInfo)) {
            throw new Zy_Core_Exception(405, "操作失败, 用户不存在或账号密码错误");
        }

        $profile = array(
            'passport' => $newPassword,
        );

        $ret = $serviceData->editUserInfo(intval($userInfo['uid']), $profile);
        if ($ret == false) {
            throw new Zy_Core_Exception(405, "重置错误, 请重试");
        }
        
        return array();
    }
}