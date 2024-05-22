<?php
class Controller_Plan extends Zy_Core_Controller{

    public $actions = array(
        "lists"         => "actions/plan/Lists.php",
        "create"        => "actions/plan/Create.php",
        "update"        => "actions/plan/Update.php",
        "delete"        => "actions/plan/Delete.php",
    );
}
