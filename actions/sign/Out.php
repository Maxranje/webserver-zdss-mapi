<?php

class Actions_Out extends Zy_Core_Actions {

    // 执行入口
    public function execute() {
        if (!$this->isLogin()) {
            $this->redirectLogin();
        }
        $serivce = new Service_Page_Account_SignOut ($this->_request, $this->_userInfo);
        $serivce->execute();
        $this->redirectLogin();
    }

}