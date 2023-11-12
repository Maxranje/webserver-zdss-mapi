<?php
class Controller_Clasze extends Zy_Core_Controller{

    public $actions = array(
        "lists"     => "actions/Clasze/Lists.php",
        "create"    => "actions/Clasze/Create.php",
        "update"    => "actions/Clasze/Update.php",
        "delete"    => "actions/Clasze/Delete.php",

        // 映射配置
        "maplists"     => "actions/Clasze/Maplists.php",
        "mapcreate"    => "actions/Clasze/Mapcreate.php",
        "mapupdate"    => "actions/Clasze/Mapupdate.php",
        "mapdelete"    => "actions/Clasze/Mapdelete.php",
    );
}
