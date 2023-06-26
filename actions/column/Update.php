<?php

class Actions_Update extends Zy_Core_Actions {

    // 执行入口
    public function execute() {
        if (!$this->isLogin()) {
            return $this->_data;
        }

        $serivce = new Service_Page_Column_Update ($this->_request, $this->_userInfo);
        $this->_data = $serivce->execute();
        return $this->_data;
    }

}