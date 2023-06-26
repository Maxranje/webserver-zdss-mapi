<?php
class Controller_Admins extends Zy_Core_Controller{

    public $actions = array(
        "lists"         => "actions/admins/Lists.php",
        "create"        => "actions/admins/Create.php",
        "update"        => "actions/admins/Update.php",
        "delete"        => "actions/admins/Delete.php",
    );
}
