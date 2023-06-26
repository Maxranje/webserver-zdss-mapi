<?php
class Controller_Area extends Zy_Core_Controller{

    public $actions = array(
        "lists"         => "actions/area/Lists.php",
        "onlyarea"      => "actions/area/Onlyarea.php",
        "withroom"      => "actions/area/Withroom.php",
        "create"        => "actions/area/Create.php",
        "update"        => "actions/area/Update.php",
        "delete"        => "actions/area/Delete.php",
        "details"       => "actions/area/Details.php",
    );
}
