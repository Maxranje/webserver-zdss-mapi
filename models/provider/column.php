<?php

class Dao_Column extends Zy_Core_Dao {

    public function __construct() {
        $this->_dbName      = "zy_mapiv2";
        $this->_table       = "tblColumn";
        $this->arrFieldsMap = array(
            "id"  => "id" , 
            "teacher_uid"  => "teacher_uid" , 
            "subject_id"  => "subject_id" , 
            "price"  => "price" , 
            "muilt_price" => "muilt_price",
            "muilt_num" => "muilt_num",
            "create_time"  => "create_time" , 
            "update_time"  => "update_time" , 
            "ext"  => "ext" , 
        );
    }
}