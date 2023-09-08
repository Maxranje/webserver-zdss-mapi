<?php

class Actions_Page extends Zy_Core_Actions {

    // 执行入口
    public function execute() {
        if (!$this->isLogin()) {
            $this->displayTemplate("login");
        }
        if ($this->_userInfo['type'] == Service_Data_Profile::USER_TYPE_ADMIN
            || $this->_userInfo['type'] == Service_Data_Profile::USER_TYPE_PARTNER
            || $this->_userInfo['type'] == Service_Data_Profile::USER_TYPE_SUPER) {
            $this->redirect("/mapi/dashboard/page");
        }
        if ($this->_userInfo['type'] == Service_Data_Profile::USER_TYPE_STUDENT) {
            $this->redirect("/mapi/dashboard/home");        
        }
        if ($this->_userInfo['type'] == Service_Data_Profile::USER_TYPE_TEACHER) {
            if (empty($this->_userInfo['pages'])) {
                $this->redirect("/mapi/dashboard/home");
            } else {
                $this->redirect("/mapi/dashboard/page");        
            }
        }
    }
}