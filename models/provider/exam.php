<?php

class Dao_Exam extends Zy_Core_Dao {

    public $arrFieldsMap;

    public function __construct() {
        $this->_dbName      = "zy_mapiv2";
        $this->_table       = "tblExam";
        $this->arrFieldsMap = array(
            "id" => "id",
            "indentify" => "indentify",
            "pid" => "pid",
            "paper_type" => "paper_type",
            "total_score" => "total_score",
            "pass_score" => "pass_score",
            "start_time" => "start_time",
            "end_time" => "end_time",
            "expire_time" => "expire_time",
            "remark" => "remark",
            "teacher_uid" => "teacher_uid",
            "status" => "status",
            "operator" => "operator",
            "update_time" => "update_time",
            "create_time" => "create_time",
            "ext" => "ext",
        );
    }
}