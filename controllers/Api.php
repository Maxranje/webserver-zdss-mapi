<?php
class Controller_Api extends Zy_Core_Controller{

    public $actions = array(
        // 助教列表
        "areaoperator"  => "actions/admins/Areaoperator.php",
        "soplists"      => "actions/admins/Soplists.php",
        "reset"         => "actions/student/Reset.php",
        "notice"        => "actions/api/Notice.php",
        "apconfirm"     => "actions/api/Apconfirm.php",
    );
}
