<?php
class Controller_Student extends Zy_Core_Controller{

    public $actions = array(
        "lists"         => "actions/student/Lists.php",
        "create"        => "actions/student/Create.php",
        "update"        => "actions/student/Update.php",
        "delete"        => "actions/student/Delete.php",
        "batchcreate"   => "actions/student/Batchcreate.php",
        "recharge"      => "actions/student/Recharge.php",
        "refund"        => "actions/student/Refund.php",
        "remark"        => "actions/student/Remark.php",

        // 学生图表数据
        "soplists"      => "actions/student/sop/Lists.php",
    );
}
