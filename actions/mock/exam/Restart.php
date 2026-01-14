<?php

class Actions_Restart extends Zy_Core_Actions {

    // 执行入口
    public function execute() {
        if (!$this->isLogin() ) {
            $this->error(401, "请先登录");
        }

        $serivce = new Service_Page_Mock_Exam_Restart ($this->_request, $this->_userInfo);
        $this->_data = $serivce->execute();
        return $this->_data;
    }

}