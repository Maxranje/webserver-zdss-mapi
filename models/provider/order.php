<?php

class Dao_Order extends Zy_Core_Dao {

    public $arrFieldsMap;

    public function __construct() {
        $this->_dbName      = "zy_mapiv2";
        $this->_table       = "tblOrder";
        $this->arrFieldsMap = array(
            "order_id" => "order_id",
            "subject_id" => "subject_id",
            "student_uid" => "student_uid",
            "balance" => "balance",
            "price" => "price",
            "discount" => "discount",
            "discount_type" => "discount_type",
            "transfer_id" => "transfer_id",
            "operator" => "operator",
            "update_time" => "update_time",
            "create_time" => "create_time",
            "ext" => "ext",
        );
    }
}