<?php

class Dao_Refund extends Zy_Core_Dao {

    public $arrFieldsMap;

    public function __construct() {
        $this->_dbName      = "zy_mapiv2";
        $this->_table       = "tblRefund";
        $this->arrFieldsMap = array(
            "id" => "id",
            "order_id" => "order_id",
            "student_uid" => "student_uid",
            "balance" => "balance",
            "operator" => "operator",
            "schedule_nums" => "schedule_nums",
            "update_time" => "update_time",
            "create_time" => "create_time",
            "ext" => "ext",
        );
    }
}