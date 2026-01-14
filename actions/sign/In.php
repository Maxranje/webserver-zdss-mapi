<?php

class Actions_In extends Zy_Core_Actions {

    // 执行入口
    public function execute() {
        if ($this->isLogin()) {
            $serivce = new Service_Page_Account_SignProfile($this->_request, $this->_userInfo);
            return $serivce->execute();
        }

        $serivce = new Service_Page_Account_SignIn ($this->_request, $this->_userInfo);
        return $serivce->execute();
    }

}