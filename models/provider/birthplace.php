<?php

class Dao_Birthplace extends Zy_Core_Dao {

    public $arrFieldsMap;

    public function __construct() {
        $this->_dbName      = "zy_mapiv2";
        $this->_table       = "tblBirthplace";
        $this->arrFieldsMap = array(
            "id"  => "id" , 
            "name"  => "name", 
            "create_time"  => "create_time" , 
            "update_time"  => "update_time" , 
            "ext"  => "ext" , 
        );
    }
}