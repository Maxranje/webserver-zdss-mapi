<?php

class Actions_Page extends Zy_Core_Actions {

    // 执行入口
    public function execute() {
        if (!$this->isLogin()) {
            $this->redirectLogin();
        } 
        // 管理员 & 超管 & 合作商
        if ($this->_userInfo['type'] == Service_Data_Profile::USER_TYPE_ADMIN
            || $this->_userInfo['type'] == Service_Data_Profile::USER_TYPE_PARTNER
            || $this->_userInfo['type'] == Service_Data_Profile::USER_TYPE_SUPER) {
            $this->redirect("/mapi/dashboard/page");
        } else if (!empty($this->_userInfo['pages']) && 
            $this->_userInfo['type'] == Service_Data_Profile::USER_TYPE_TEACHER) { // 有权限的老师
            $this->redirect("/mapi/dashboard/page"); 
        } else { // 都不是,
            $this->redirectLogin();
        }
    }
}