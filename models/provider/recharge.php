<?php

class Dao_Recharge extends Zy_Core_Dao {

    public $arrFieldsMap;

    public function __construct() {
        $this->_dbName      = "zy_mapiv2";
        $this->_table       = "tblRecharge";
        $this->arrFieldsMap = array(
            "id" => "id",
            "order_id" => "order_id",
            "type" => "type",
            "student_uid" => "student_uid",
            "new_balance" => "new_balance",
            "balance" => "balance",
            "operator" => "operator",
            "update_time" => "update_time",
            "create_time" => "create_time",
            "ext" => "ext",
        );
    }
}