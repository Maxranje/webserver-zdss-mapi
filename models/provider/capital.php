<?php

class Dao_Capital extends Zy_Core_Dao {

    public $arrFieldsMap;

    public function __construct() {
        $this->_dbName      = "zy_mapiv2";
        $this->_table       = "tblCapital";
        $this->arrFieldsMap = array(
            "id" => "id",
            "uid" => "uid",
            "type" => "type",
            "plan_id" => "plan_id",
            "operator" => "operator",
            "capital" => "capital",
            "update_time" => "update_time",
            "create_time" => "create_time",
            "ext" => "ext",
        );
    }
}