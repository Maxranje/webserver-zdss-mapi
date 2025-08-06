<?php
class Controller_Column extends Zy_Core_Controller{

    public $actions = array(
        "lists"     => "actions/column/Lists.php",
        "create"    => "actions/column/Create.php",
        "update"    => "actions/column/Update.php",
        "delete"    => "actions/column/Delete.php",

        // 科目下的老师列表
        "teacherlists"  => "actions/column/Teacherlists.php",
    );
}
