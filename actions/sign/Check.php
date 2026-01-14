<?php

class Actions_Check extends Zy_Core_Actions {

    // 执行入口
    public function execute() {
        if (!$this->isLogin()) {
            $this->error(401, "请先登录");
        }
        
        // 重置 token 和用户信息
        if (!Zy_Helper_Authtoken::validateToken($this->_userid)) {
            $serivce = new Service_Page_Account_SignProfile($this->_request, $this->_userInfo);
            return $serivce->execute();
        }
        return array();
    }
}