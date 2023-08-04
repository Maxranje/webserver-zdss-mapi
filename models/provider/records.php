<?php

class Dao_Records extends Zy_Core_Dao {

    public $arrFieldsMap = array();

    public function __construct() {
        $this->_dbName      = "zy_mapiv2";
        $this->_table       = "tblRecords";
        $this->arrFieldsMap = array(
            "id" => "id",
            "uid" => "uid",
            "type" => "type",
            "state" => "state",
            "group_id" => "group_id",
            "order_id" => "order_id",
            "subject_id" => "subject_id",
            "teacher_uid" => "teacher_uid",
            "schedule_id" => "schedule_id",
            "category" => "category",
            "operator" => "operator",
            "money" => "money",
            "update_time" => "update_time",
            "create_time" => "create_time",
            "ext" => "ext",
        );
    }
}