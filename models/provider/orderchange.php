<?php

class Dao_Orderchange extends Zy_Core_Dao {

    public $arrFieldsMap;

    public function __construct() {
        $this->_dbName      = "zy_mapiv2";
        $this->_table       = "tblOrderChange";
        $this->arrFieldsMap = array(
            "id" => "id",
            "order_id" => "order_id",
            "student_uid" => "student_uid",
            "type" => "type",
            "balance" => "balance",
            "duration" => "duration",
            "order_info" => "order_info",
            "operator" => "operator",
            "update_time" => "update_time",
            "create_time" => "create_time",
            "ext" => "ext",
        );
    }
}