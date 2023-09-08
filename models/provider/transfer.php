<?php

class Dao_Transfer extends Zy_Core_Dao {

    public $arrFieldsMap;

    public function __construct() {
        $this->_dbName      = "zy_mapiv2";
        $this->_table       = "tblTransfer";
        $this->arrFieldsMap = array(
            "id" => "id",
            "order_id" => "order_id",
            "student_uid" => "student_uid",
            "transfer_id" => "transfer_id",
            "balance" => "balance",
            "schedule_nums" => "schedule_nums",
            "operator" => "operator",
            "update_time" => "update_time",
            "create_time" => "create_time",
            "ext" => "ext",
        );
    }
}