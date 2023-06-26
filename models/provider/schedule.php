<?php

class Dao_Schedule extends Zy_Core_Dao {

    public $arrFieldsMap;

    public function __construct() {
        $this->_dbName      = "zy_mapi";
        $this->_table       = "tblSchedule";
        $this->arrFieldsMap = array(
            "id"  => "id" , 
            "group_id"  => "group_id" , 
            "column_id"  => "column_id" , 
            "area_id"   => "area_id",
            "room_id"   => "room_id",
            "area_op" => "area_op",
	        "teacher_id" => "teacher_id",
	        "start_time"  => "start_time" , 
            "operator" => "operator",
            "end_time"  => "end_time" , 
            "state"  => "state" , 
            "create_time"  => "create_time" , 
            "update_time"  => "update_time" , 
            "ext"  => "ext" , 
        );
    }
}
