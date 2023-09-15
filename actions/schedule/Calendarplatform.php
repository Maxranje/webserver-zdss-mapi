<?php

class Actions_Calendarplatform extends Zy_Core_Actions {

    // 执行入口
    public function execute() {
        if (!$this->isLogin()) {
            $this->error(405, "请先登录");
        }

        $this->displayTemplate("calendar");
    }

}