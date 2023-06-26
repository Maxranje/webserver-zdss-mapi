<?php
class Controller_Subject extends Zy_Core_Controller{

    public $actions = array(
        "lists"     => "actions/subject/Lists.php",
        "create"    => "actions/subject/Create.php",
        "update"    => "actions/subject/Update.php",
        "delete"    => "actions/subject/Delete.php",
    );
}
