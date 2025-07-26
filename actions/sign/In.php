<?php

class Actions_In extends Zy_Core_Actions {

    // 执行入口
    public function execute() {
        // 是否线下测试
        if (!Zy_Helper_Utils::isDev()) {
            $this->error(405, "能力已下线");
        }

        if ($this->isLogin()) {
            $this->error(405, "您已经登录, 请刷新页面");
        } 

        $serivce = new Service_Page_Account_SignIn ($this->_request, $this->_userInfo);
        $this->_data = $serivce->execute();
        return $this->_data;
    }

}