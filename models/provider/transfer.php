<?php

class Dao_Transfer extends Zy_Core_Dao {

    public $arrFieldsMap;

    public function __construct() {
        $this->_dbName      = "zy_mapiv2";
        $this->_table       = "tblTransfer";
        $this->arrFieldsMap = array(
            "id" => "id",
            "type" => "type",
            "order_id" => "order_id",
            "student_uid" => "student_uid",
            "transfer_id" => "transfer_id",
            "balance" => "balance",
            "operator" => "operator",
            "update_time" => "update_time",
            "create_time" => "create_time",
            "ext" => "ext",
        );
    }
}