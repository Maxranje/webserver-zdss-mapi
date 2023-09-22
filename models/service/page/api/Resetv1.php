<?php

class Service_Page_Api_Resetv1 extends Zy_Core_Service{

    public function execute () {
        $username  = empty($this->request['username']) ? "" : trim($this->request['username']);
        $passport  = empty($this->request['passport']) ? "" : trim($this->request['passport']);
        $reset     = empty($this->request['reset']) ? "" : trim($this->request['reset']);
        if (empty($username) || empty($passport) || empty($reset)) {
            throw new Zy_Core_Exception(405, "操作失败, 输入内容不能为空");
        }

        if ($passport == $reset) {
            throw new Zy_Core_Exception(405, "操作失败, 重置密码与当前密码一致");
        }

        if (!Zy_Helper_Utils::checkStr($reset) || !Zy_Helper_Utils::checkStr($passport) || !Zy_Helper_Utils::checkStr($username)) {
            throw new Zy_Core_Exception(405, "操作失败, 输入内容必须是中文,英文,数字或逗号");
        }

        if (mb_strlen($passport) > 12 || mb_strlen($passport) < 6) {
            throw new Zy_Core_Exception(405, "操作失败, 输入密码必须6-12位");
        }

        if (mb_strlen($reset) > 12 || mb_strlen($reset) < 6) {
            throw new Zy_Core_Exception(405, "操作失败, 输入密码必须6-12位");
        }

        $serviceData = new Service_Data_Profile();
        $userInfo = $serviceData->getUserInfoByNameAndPass($username, $passport);
        if (empty($userInfo)) {
            throw new Zy_Core_Exception(405, "操作失败, 用户不存在或账号密码错误");
        }

        $profile = array(
            'passport' => $reset,
        );

        $ret = $serviceData->editUserInfo(intval($userInfo['uid']), $profile);
        if ($ret == false) {
            throw new Zy_Core_Exception(405, "重置错误, 请重试");
        }
        
        return array();
    }
}