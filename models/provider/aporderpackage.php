<?php

class Dao_Aporderpackage extends Zy_Core_Dao {

    public $arrFieldsMap;

    public function __construct() {
        $this->_dbName      = "zy_mapiv2";
        $this->_table       = "tblAporderpackage";

        $this->arrFieldsMap = array(
            "id" => "id",
            "uid" => "uid",
            "abroadplan_id" => "abroadplan_id",
            "schedule_nums" => "schedule_nums",
            "price" => "price",
            "state" => "state",
            "operator" => "operator",
            "remark" => "remark",
            "update_time" => "update_time",
            "create_time" => "create_time",
            "ext" => "ext",
        );        
    }
}