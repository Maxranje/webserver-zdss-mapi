<?php
class Controller_Order extends Zy_Core_Controller{

    public $actions = array(
        "lists"     => "actions/order/Lists.php",
        "create"    => "actions/order/Create.php",
        "delete"    => "actions/order/Delete.php",
        "detail"    => "actions/order/Detail.php",
        "band"      => "actions/order/Band.php",
        "review"    => "actions/order/Review.php",

        // 充值
        "rechargecreate"    => "actions/order/Rechargecreate.php",
        "rechargelists"     => "actions/order/Rechargelists.php",
        // 优惠配置
        "discountupdate"    => "actions/order/Discountupdate.php",
        // 结转
        "transfercreate"    => "actions/order/Transfercreate.php",
        "transferlists"     => "actions/order/Transferlists.php",
        "transferreview"     => "actions/order/Transferreview.php",
        // 退款
        "refundcreate"      => "actions/order/Refundcreate.php",
        "refundlists"       => "actions/order/Refundlists.php",
        "refundreview"      => "actions/order/Refundreview.php",
    );
}
