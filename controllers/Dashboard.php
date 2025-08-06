<?php
class Controller_Dashboard extends Zy_Core_Controller{

    public $actions = array(
        "page"     => "actions/dashboard/Page.php",
        "menu"     => "actions/dashboard/Menu.php",

        // mock 相关
        "mock"          => "actions/dashboard/Mock.php",
        "navigation"    => "actions/dashboard/Navigation.php",
    );
}
