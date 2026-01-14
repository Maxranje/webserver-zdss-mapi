<?php
class Controller_Schedule extends Zy_Core_Controller{

    public $actions = array(
        "create"        => "actions/schedule/Create.php",
        "createv2"      => "actions/schedule/Createv2.php",
        "lists"         => "actions/schedule/Lists.php",
        "update"        => "actions/schedule/Update.php",
        "delete"        => "actions/schedule/Delete.php",
        "checkout"      => "actions/schedule/Checkout.php",
        "fscalendar"    => "actions/schedule/Fscalendar.php",
        "timelist"      => "actions/schedule/Timelist.php",
        "revoke"        => "actions/schedule/Revoke.php",

        // 绑定列表
        "bandlists"           => "actions/schedule/Bandlists.php",
        "bandcreate"          => "actions/schedule/Bandcreate.php",
        "bandapcreate"        => "actions/schedule/Bandapcreate.php",
        "bandtotal"           => "actions/schedule/Bandtotal.php",

        // 结算
        "checkoutlists"     => "actions/schedule/Checkoutlists.php",
        "checkoutsingle"    => "actions/schedule/Checkoutsingle.php",

        // 区域
        "arealists"     => "actions/schedule/Arealists.php",
        "areaupdate"    => "actions/schedule/Areaupdate.php",
        "areamodify"    => "actions/schedule/AreaModify.php",
    );
}
