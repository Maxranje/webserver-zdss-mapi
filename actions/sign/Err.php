<?php

class Actions_Err extends Zy_Core_Actions {

    // 执行入口
    public function execute() {
        $this->displayTemplate("error");
    }
}