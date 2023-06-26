<?php
class Controller_Teacher extends Zy_Core_Controller{

    public $actions = array(
        "lists"     => "actions/teacher/Lists.php",
        "create"    => "actions/teacher/Create.php",
        "update"    => "actions/teacher/Update.php",
        "delete"    => "actions/teacher/Delete.php",
        "batchcreate"    => "actions/teacher/Batchcreate.php",
    );
}
