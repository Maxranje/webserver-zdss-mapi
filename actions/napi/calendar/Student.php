<?php

class Actions_Student extends Zy_Core_Actions {

    // 执行入口
    public function execute() {
        if (!$this->isLogin()) {
            $this->error(401, "请先登录");
        }
        $serivce = new Service_Page_Napi_Calendar_Student ($this->_request, $this->_userInfo);
        return $serivce->execute();
    }
}