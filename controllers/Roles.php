<?php
class Controller_Roles extends Zy_Core_Controller{

    public $actions = array(
        "lists"         => "actions/roles/Lists.php",
        "create"        => "actions/roles/Create.php",
        "update"        => "actions/roles/Update.php",
        "delete"        => "actions/roles/Delete.php",
        "userlists"     => "actions/roles/Userlists.php",
    );
}
