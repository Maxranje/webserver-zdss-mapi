<?php
class Controller_Sign extends Zy_Core_Controller{

    public $actions = array(
        "err"       => "actions/sign/Err.php",
        "page"      => "actions/sign/Page.php",
        "in"        => "actions/sign/In.php",
        "out"       => "actions/sign/Out.php",
        "reset"     => "actions/sign/Reset.php",
        "resetpage" => "actions/sign/Resetpage.php",
    );
}
