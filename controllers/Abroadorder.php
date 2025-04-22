<?php
class Controller_Abroadorder extends Zy_Core_Controller{

    public $actions = array(
        // 计划订单
        "lists"    => "actions/abroadorder/Lists.php",
        "create"   => "actions/abroadorder/Create.php",
        "delete"   => "actions/abroadorder/Delete.php",
        "update"   => "actions/abroadorder/Update.php",
        "detail"   => "actions/abroadorder/Detail.php",
        "describe" => "actions/abroadorder/Describe.php",
        "unbind"   => "actions/abroadorder/Unbind.php",

        // 服务
        "packagelists"    => "actions/abroadorder/Packagelists.php",
        "packagereview"   => "actions/abroadorder/Packagereview.php",
        "packagecreate"   => "actions/abroadorder/Packagecreate.php",
        "packagedelete"   => "actions/abroadorder/Packagedelete.php",
        "packagedone"     => "actions/abroadorder/Packagedone.php",  
        "packageduration" => "actions/abroadorder/Packageduration.php",    
        "packagedetail"   => "actions/abroadorder/Packagedetail.php",   
        "packagetransfer" => "actions/abroadorder/Packagetransfer.php",      
        "packageunbind"   => "actions/abroadorder/PackageUnbind.php",

        // 变更记录
        "changelists" => "actions/abroadorder/Changelists.php", 

        // 检查项
        "confirmupdate" => "actions/abroadorder/Confirmupdate.php",         
        "confirmdetail" => "actions/abroadorder/Confirmdetail.php",   
        "confirmupload" => "actions/abroadorder/Confirmupload.php",   
        "confirmdown"   => "actions/abroadorder/Confirmdown.php",
    );
}
