<?php
class Controller_Teacher extends Zy_Core_Controller{

    public $actions = array(
        "lists"     => "actions/teacher/Lists.php",
        "create"    => "actions/teacher/Create.php",
        "update"    => "actions/teacher/Update.php",
        "salary"    => "actions/teacher/Salary.php",
        "delete"    => "actions/teacher/Delete.php",
        "batchcreate"    => "actions/teacher/Batchcreate.php",

        // 锁定
        "locklists"     => "actions/teacher/Locklists.php",
        "lockcreate"    => "actions/teacher/Lockcreate.php",
        "lockdelete"    => "actions/teacher/Lockdelete.php",

        // 排课
        "schedulelists"     => "actions/teacher/Schedulelists.php",
    );
}
