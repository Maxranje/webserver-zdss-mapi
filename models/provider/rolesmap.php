<?php

class Dao_Rolesmap extends Zy_Core_Dao {

    public $arrFieldsMap;

    public function __construct() {
        $this->_dbName      = "zy_mapi";
        $this->_table       = "tblRoleMap";
        $this->arrFieldsMap = array(
            "id"  => "id" , 
            "role_id"  => "role_id", 
            "descs"  => "descs", 
            "uid"  => "uid", 
            "create_time"  => "create_time" , 
            "update_time"  => "update_time" , 
            "ext"  => "ext" , 
        );
    }
}