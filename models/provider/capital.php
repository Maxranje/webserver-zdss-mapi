<?php

class Dao_Capital extends Zy_Core_Dao {

    public $arrFieldsMap;

    public function __construct() {
        $this->_dbName      = "zy_mapiv2";
        $this->_table       = "tblCapital";
        $this->arrFieldsMap = array(
            "id" => "id",
            "uid" => "uid",
            "state" => "state",
            "type" => "type",
            "abroadplan_id" => "abroadplan_id",
            "operator" => "operator",
            "capital" => "capital",
            "rop_uid" => "rop_uid",
            "update_time" => "update_time",
            "create_time" => "create_time",
            "ext" => "ext",
        );
    }
}