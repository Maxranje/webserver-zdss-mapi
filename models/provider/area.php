<?php

class Dao_Area extends Zy_Core_Dao {

    public $arrFieldsMap;

    public function __construct() {
        $this->_dbName      = "zy_mapiv2";
        $this->_table       = "tblArea";
        $this->arrFieldsMap = array(
            "id"  => "id" , 
            "name"  => "name", 
            "is_online" => "is_online",
            "create_time"  => "create_time" , 
            "update_time"  => "update_time" , 
            "ext"  => "ext" , 
        );
    }
}