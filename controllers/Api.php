<?php
class Controller_Api extends Zy_Core_Controller{

    public $actions = array(
        // 助教列表
        "areaoperator" => "actions/admins/Areaoperator.php",
        "reset"        => "actions/student/Reset.php",
    );
}
