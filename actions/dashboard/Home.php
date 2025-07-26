<?php

class Actions_Home extends Zy_Core_Actions {
    // 执行入口
    public function execute() {
        $this->redirectLogin();
    }
}