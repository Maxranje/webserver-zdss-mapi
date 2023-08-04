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
            "total_balance" => "total_balance",
            "discount" => "discount",
            "discount_type" => "discount_type",
            "is_transfer" => "is_transfer",
            "transfer_id" => "transfer_id",
            "is_refund" => "is_refund",
            "refund_id" => "refund_id",
            "update_time" => "update_time",
            "create_time" => "create_time",
            "ext" => "ext",
        );
    }
}