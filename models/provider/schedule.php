<?php

class Dao_Schedule extends Zy_Core_Dao {

    public $arrFieldsMap;

    public function __construct() {
        $this->_dbName      = "zy_mapiv2";
        $this->_table       = "tblSchedule";
        $this->arrFieldsMap = array(
            "id" => "id",
            "column_id" => "column_id",
            "group_id" => "group_id",
            "subject_id" => "subject_id",
            "teacher_uid" => "teacher_uid",
            "start_time" => "start_time",
            "end_time" => "end_time",
            "state" => "state",
            "operator" => "operator",
            "area_id" => "area_id",
            "room_id" => "room_id",
            "area_operator" => "area_operator",
            "update_time" => "update_time",
            "create_time" => "create_time",
            "ext" => "ext",
        );
    }
}
