<?php

class Actions_Teacherlists extends Zy_Core_Actions {

    // 执行入口
    public function execute() {
        if (!$this->isLogin()) {
            $this->error(405, "请先登录");
        }

        $serivce = new Service_Page_Column_Teacher_Lists ($this->_request, $this->_userInfo);
        $this->_data = $serivce->execute();
        return $this->_data;
    }

}