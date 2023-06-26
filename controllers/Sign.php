<?php
class Controller_Sign extends Zy_Core_Controller{

    public $actions = array(
        "page" => "actions/sign/Page.php",
        "in"    => "actions/sign/In.php",
        "out"   => "actions/sign/Out.php",
    );
}
