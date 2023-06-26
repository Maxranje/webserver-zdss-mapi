<?php

class Zy_Core_Controller {

    public $actions = array();

    // 初始化Action所需要内容
    public function _init ($controller, $action) {
        if (empty($this->actions) || empty($this->actions[strtolower($action)])) {
            trigger_error ('Error] router error [Detail] action conf or action not found '.get_class() . " - " . $action);
        }

        require_once(sprintf("%sactions/%s/%s.php", BASEPATH, strtolower($controller), $action));
        if ( !class_exists('Actions_' . $action, FALSE) ) {
            trigger_error ('Error] router error [Detail] class not found "Actions_'. $action .'"');
        }
        
        $actionClass = 'Actions_' . $action;
        if (!method_exists($actionClass, "_init")) {
            trigger_error ('Error] router error [Detail] action class not init "Actions_'. $action .'"');
        }
        
        call_user_func([new $actionClass, '_init']);
    }
}