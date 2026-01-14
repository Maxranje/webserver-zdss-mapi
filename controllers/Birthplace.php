<?php
class Controller_Birthplace extends Zy_Core_Controller{

    public $actions = array(
        "lists"         => "actions/birthplace/Lists.php",
        "create"        => "actions/birthplace/Create.php",
        "update"        => "actions/birthplace/Update.php",
        "delete"        => "actions/birthplace/Delete.php",
    );
}
