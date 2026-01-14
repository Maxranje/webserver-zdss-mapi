<?php

class Actions_Check extends Zy_Core_Actions {

    // 执行入口
    public function execute() {
        if (!$this->isLogin() ) {
            $this->error(401, "请先登录");
        }

        $serivce = new Service_Page_Napi_Abroadplan_Check ($this->_request, $this->_userInfo);
        $this->_data = $serivce->execute();
        return $this->_data;
    }

}