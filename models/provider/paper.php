<?php

class Dao_Paper extends Zy_Core_Dao {

    public $arrFieldsMap;

    public function __construct() {
        $this->_dbName      = "zy_mapiv2";
        $this->_table       = "tblPaper";
        $this->arrFieldsMap = array(
            "pid" => "pid",
            "title" => "title",
            "descs" => "descs",
            "subject_id" => "subject_id",
            "source" => "source",
            "frequency" => "frequency",
            "level" => "level",
            "question_cnt" => "question_cnt",
            "question_score" => "question_score",
            "operator" => "operator",
            "update_time" => "update_time",
            "create_time" => "create_time",
            "ext" => "ext",
        );
    }
}