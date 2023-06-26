<?php

class Dao_Group extends Zy_Core_Dao {

    public function __construct() {
        $this->_dbName      = "zy_mapi";
        $this->_table       = "tblGroup";
        $this->arrFieldsMap = array(
            "id"  => "id" , 
            "name"  => "name", 
            "descs"  => "descs" ,
            "status" => "status",
            "price" => "price",
            "area_op" => "area_op",
            "discount" => "discount",
            "duration" => "duration" ,
            "student_price" => "student_price",
            "create_time"  => "create_time" , 
            "update_time"  => "update_time" , 
            "ext"  => "ext" , 
        );
    }
}