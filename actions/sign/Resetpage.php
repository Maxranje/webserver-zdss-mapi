<?php

class Actions_Resetpage extends Zy_Core_Actions {

    // 执行入口
    public function execute() {
        $this->displayTemplate("reset");
    }
}