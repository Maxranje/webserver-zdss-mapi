<?php

class Actions_Out extends Zy_Core_Actions {

    // 执行入口
    public function execute() {
        // 是否线下测试
        if (!Zy_Helper_Utils::isDev()) {
            $this->error(405, "能力已下线");
        }

        if (!$this->isLogin()) {
            $this->redirectLogin();
        }
        $serivce = new Service_Page_Account_SignOut ($this->_request, $this->_userInfo);
        $serivce->execute();
        $this->redirectLogin();
    }

}