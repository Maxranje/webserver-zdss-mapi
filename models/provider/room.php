<?php

class Dao_Room extends Zy_Core_Dao {

    public $arrFieldsMap;

    public function __construct() {
        $this->_dbName      = "zy_mapi";
        $this->_table       = "tblRoom";
        $this->arrFieldsMap = array(
            "id"  => "id" , 
            "name"  => "name", 
            "area_id"  => "area_id",
            "create_time"  => "create_time" , 
            "update_time"  => "update_time" , 
            "ext"  => "ext" , 
        );
    }
}