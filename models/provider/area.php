<?php

class Dao_Area extends Zy_Core_Dao {

    public $arrFieldsMap;

    public function __construct() {
        $this->_dbName      = "zy_mapi";
        $this->_table       = "tblArea";
        $this->arrFieldsMap = array(
            "id"  => "id" , 
            "name"  => "name", 
            "create_time"  => "create_time" , 
            "update_time"  => "update_time" , 
            "ext"  => "ext" , 
        );
    }
}