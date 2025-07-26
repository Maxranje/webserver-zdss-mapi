<?php

class Actions_Reset extends Zy_Core_Actions {

    // 执行入口
    public function execute() {
        if ($this->isLogin() ) {
            $this->error(405, "已登录, 需要退出登录后修改");
        }

        $serivce = new Service_Page_Account_SignReset ($this->_request, $this->_userInfo);
        $this->_data = $serivce->execute();
        return $this->_data;
    }
}