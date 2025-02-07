<?php
class Controller_Abroadplan extends Zy_Core_Controller{

    public $actions = array(
        "lists"         => "actions/abroadplan/Lists.php",
        "create"        => "actions/abroadplan/Create.php",
        "update"        => "actions/abroadplan/Update.php",
        "delete"        => "actions/abroadplan/Delete.php",
        "confirm"       => "actions/abroadplan/Confirm.php",
    );
}