<?php

class Actions_Menu extends Zy_Core_Actions {

    public function execute() {
        if (!$this->isLogin() ) {
            $this->error(405, "请先登录");
        }

        $serivce = new Service_Page_Api_Menu ($this->_request, $this->_userInfo);
        $this->_data = $serivce->execute();
        return $this->_data;
    }

}