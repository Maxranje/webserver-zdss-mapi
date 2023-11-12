<?php

class Actions_Mapupdate extends Zy_Core_Actions {

    // 执行入口
    public function execute() {
        if (!$this->isLogin()) {
            return $this->_data;
        }

        $serivce = new Service_Page_Clasze_Map_Update ($this->_request, $this->_userInfo);
        $this->_data = $serivce->execute();
        return $this->_data;
    }

}