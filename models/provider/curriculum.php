<?php

class Dao_Curriculum extends Zy_Core_Dao {

    public $arrFieldsMap;

    public function __construct() {
        $this->_dbName      = "zy_mapiv2";
        $this->_table       = "tblCurriculum";
        $this->arrFieldsMap = array(
            "id" => "id",
            "schedule_id" => "schedule_id",
            "student_uid" => "student_uid",
            "order_id" => "order_id",
            "column_id" => "column_id",
            "group_id" => "group_id",
            "subject_id" => "subject_id",
            "teacher_uid" => "teacher_uid",
            "start_time" => "start_time",
            "end_time" => "end_time",
            "state" => "state",
            "area_id" => "area_id",
            "room_id" => "room_id",
            "sop_uid" => "sop_uid",
            "update_time" => "update_time",
            "create_time" => "create_time",
            "ext" => "ext",
        );
    }
}
