<?php

class Dao_Groupmap extends Zy_Core_Dao {

    public function __construct() {
        $this->_dbName      = "zy_mapi";
        $this->_table       = "tblGroupMap";
        $this->arrFieldsMap = array(
            "id"  => "id" , 
            "group_id"  => "group_id", 
            "student_id"  => "student_id" ,
            "create_time"  => "create_time" , 
            "update_time"  => "update_time" , 
            "ext"  => "ext" , 
        );
    }
}