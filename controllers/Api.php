<?php
class Controller_Api extends Zy_Core_Controller{

    public $actions = array(
        // 区域管理列表
        "areaoperator" => "actions/admins/Areaoperator.php",
        "reset"        => "actions/student/Reset.php",
    );
}
