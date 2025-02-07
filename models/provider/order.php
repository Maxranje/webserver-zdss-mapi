<?php

class Dao_Order extends Zy_Core_Dao {

    public $arrFieldsMap;

    public function __construct() {
        $this->_dbName      = "zy_mapiv2";
        $this->_table       = "tblOrder";
        $this->arrFieldsMap = array(
            "order_id" => "order_id",
            "type" => "type",
            "abroadplan_id" => "abroadplan_id",
            "apackage_id" => "apackage_id",
            "subject_id" => "subject_id",
            "student_uid" => "student_uid",
            "bpid"  => "bpid",
            "cid"   => "cid",
            "isfree" => "isfree",
            "balance" => "balance",
            "price" => "price",
            "discount_j" => "discount_j",
            "discount_z" => "discount_z",
            "operator" => "operator",
            "update_time" => "update_time",
            "create_time" => "create_time",
            "ext" => "ext",
        );
    }
}