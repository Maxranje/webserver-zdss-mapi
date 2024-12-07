<?php
class Controller_Order extends Zy_Core_Controller{

    public $actions = array(
        "lists"     => "actions/order/Lists.php",
        "create"    => "actions/order/Create.php",
        "delete"    => "actions/order/Delete.php",
        "detail"    => "actions/order/Detail.php",
        "band"      => "actions/order/Band.php",
        "review"    => "actions/order/Review.php",
        "describe"  => "actions/order/Describe.php",

        // 变更
        "changerefund"    => "actions/order/Changerefund.php",
        "changelists"     => "actions/order/Changelists.php",
        "changereview"    => "actions/order/Changereview.php",
    );
}
