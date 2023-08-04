<?php

class Dao_Lock extends Zy_Core_Dao {

    public $arrFieldsMap;

    public function __construct() {
        $this->_dbName      = "zy_mapiv2";
        $this->_table       = "tblLock";
        $this->arrFieldsMap = array(
            "id"  => "id" , 
            "type"  => "type", 
            "uid"  => "uid" , 
            "start_time"  => "start_time" , 
            "end_time"  => "end_time" , 
            "operator"  => "operator" , 
            "create_time"  => "create_time" , 
            "update_time"  => "update_time" , 
            "ext"  => "ext" , 
        );
    }
}