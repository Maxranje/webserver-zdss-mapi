<?php

class Actions_Userlists extends Zy_Core_Actions {

    // 执行入口
    public function execute() {
        if (!$this->isLogin() ) {
            $this->error(405, "请先登录");
        }

        $serivce = new Service_Page_Roles_User_Lists ($this->_request, $this->_userInfo);
        $this->_data = $serivce->execute();
        return $this->_data;
    }

}