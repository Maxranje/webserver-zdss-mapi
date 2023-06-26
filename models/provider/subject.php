<?php

class Dao_Subject extends Zy_Core_Dao {

    public function __construct() {
        $this->_dbName      = "zy_mapi";
        $this->_table       = "tblSubject";
        $this->arrFieldsMap = array(
            "id"  => "id" , 
            "category1"  => "category1", 
            "category2"  => "category2",
            "name"  => "name" , 
            "descs"  => "descs" , 
            "create_time"  => "create_time" , 
            "update_time"  => "update_time" , 
            "ext"  => "ext" , 
        );
    }
}