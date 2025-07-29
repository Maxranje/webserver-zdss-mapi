<?php

class Actions_Mock extends Zy_Core_Actions {

    // 执行入口
    public function execute() {
        if (!$this->isLogin() ) {
            $this->redirectLogin();
        }
        $this->_output['data'] = $this->_userInfo;
        $this->displayTemplate("mock");
    }
}