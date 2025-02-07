<?php

class Dao_Abroadplan extends Zy_Core_Dao {

    public $arrFieldsMap;

    public function __construct() {
        $this->_dbName      = "zy_mapiv2";
        $this->_table       = "tblAbroadplan";
        $this->arrFieldsMap = array(
            "id"  => "id" , 
            "name"  => "name", 
            "price" => "price",
            "duration" => "duration",
            "operator" => "operator",
            "create_time"  => "create_time" , 
            "update_time"  => "update_time" , 
            "ext"  => "ext",
        );
    }
}