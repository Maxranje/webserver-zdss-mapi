<?php

class Zy_Core_Controller {

    public $actions = array();

    // 初始化Action所需要内容
    public function _init ($action) {
        if (empty($this->actions) || empty($this->actions[strtolower($action)])) {
            trigger_error ('Error] router error [Detail] action conf or action not found '.get_class() . " - " . $action);
        }
        
        // actions 严格限制
        $actionFile = sprintf("%s%s", BASEPATH, $this->actions[strtolower($action)]);
        $action = explode("_", $action);
        $actionName = $action[count($action) - 1];

        if ( !file_exists($actionFile) ) {
            trigger_error ('Error] router error [Detail] action file not found "' . $actionFile . '"');
        }

        require_once($actionFile);
        if ( !class_exists('Actions_' . $actionName, FALSE) ) {
            trigger_error ('Error] router error [Detail] class not found "Actions_'. $actionName .'"');
        }
        
        $actionClass = 'Actions_' . $actionName;
        if (!method_exists($actionClass, "_init")) {
            trigger_error ('Error] router error [Detail] action class not init "Actions_'. $actionName .'"');
        }
        
        call_user_func([new $actionClass, '_init']);
    }
}