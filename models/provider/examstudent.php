<?php

class Dao_ExamStudent extends Zy_Core_Dao {

    public $arrFieldsMap;

    public function __construct() {
        $this->_dbName      = "zy_mapiv2";
        $this->_table       = "tblExamStudent";
        $this->arrFieldsMap = array(
            "id" => "id",
            "exam_id" => "exam_id",
            "pid" => "pid",
            "student_uid" => "student_uid",
            "status" => "status",
            "score" => "score",
            "operator" => "operator",
            "update_time" => "update_time",
            "ext" => "ext",
        );     
    }
}