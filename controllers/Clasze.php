<?php
class Controller_Clasze extends Zy_Core_Controller{

    public $actions = array(
        "lists"     => "actions/clasze/Lists.php",
        "create"    => "actions/clasze/Create.php",
        "update"    => "actions/clasze/Update.php",
        "delete"    => "actions/clasze/Delete.php",

        // 映射配置
        "maplists"     => "actions/clasze/Maplists.php",
        "mapcreate"    => "actions/clasze/Mapcreate.php",
        "mapupdate"    => "actions/clasze/Mapupdate.php",
        "mapdelete"    => "actions/clasze/Mapdelete.php",
    );
}
