<?php
class Controller_Area extends Zy_Core_Controller{

    public $actions = array(
        "lists"         => "actions/area/Lists.php",
        "withroom"      => "actions/area/Withroom.php",
        "create"        => "actions/area/Create.php",
        "update"        => "actions/area/Update.php",
        "delete"        => "actions/area/Delete.php",
        "details"       => "actions/area/Details.php",

        // 创建房间
        "roomcreate"      => "actions/area/Roomcreate.php",
    );
}
