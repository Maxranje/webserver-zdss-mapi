<?php
class Controller_Api extends Zy_Core_Controller{

    public $actions = array(
        "locklists"     => "actions/api/Locklists.php",
        "lockcreate"    => "actions/api/Lockcreate.php",
        "lockdelete"    => "actions/api/Lockdelete.php",
    );
}
