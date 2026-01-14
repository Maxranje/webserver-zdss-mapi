<?php
class Controller_Sign extends Zy_Core_Controller{

    public $actions = array(
        "check"     => "actions/sign/Check.php",
        "page"      => "actions/sign/Page.php",
        "in"        => "actions/sign/In.php",
        "out"       => "actions/sign/Out.php",
        "reset"     => "actions/sign/Reset.php",
    );
}
