<?php

class Actions_In extends Zy_Core_Actions {

    // 执行入口
    public function execute() {
        if ($this->isLogin()) {
            $this->error(405, "您已经登录, 请刷新页面");
        }

        $serivce = new Service_Page_Account_SignIn ($this->_request, $this->_userInfo);
        $this->_data = $serivce->execute();
        return $this->_data;
    }

}