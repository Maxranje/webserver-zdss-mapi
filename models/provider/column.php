<?php

class Dao_Column extends Zy_Core_Dao {

    public function __construct() {
        $this->_dbName      = "zy_mapi";
        $this->_table       = "tblColumn";
        $this->arrFieldsMap = array(
            "id"  => "id" , 
            "teacher_id"  => "teacher_id" , 
            "subject_id"  => "subject_id" , 
            "price"  => "price" , 
            "number" => "number",
            "muilt_price" => "muilt_price",
            "duration"  => "duration" , 
            "discount"  => "discount" , 
            "create_time"  => "create_time" , 
            "update_time"  => "update_time" , 
            "ext"  => "ext" , 
        );
    }
}