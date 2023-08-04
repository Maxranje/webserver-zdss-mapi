<?php

class Dao_Roles extends Zy_Core_Dao {

    public $arrFieldsMap;

    public function __construct() {
        $this->_dbName      = "zy_mapiv2";
        $this->_table       = "tblRole";
        $this->arrFieldsMap = array(
            "id"  => "id" , 
            "name"  => "name", 
            "descs"  => "descs", 
            "page_ids"  => "page_ids", 
            "create_time"  => "create_time" , 
            "update_time"  => "update_time" , 
            "ext"  => "ext" , 
        );
    }
}