<?php
class Controller_Api extends Zy_Core_Controller{

    public $actions = array(
        // 助教列表
        "areaoperator"  => "actions/api/Areaoperator.php",
        "soplists"      => "actions/api/Soplists.php",
        "reset"         => "actions/api/Reset.php",
        "notice"        => "actions/api/Notice.php",
    );
}
