<?php

class Dao_Paper extends Zy_Core_Dao {

    public $arrFieldsMap;

    public function __construct() {
        $this->_dbName      = "zy_mapiv2";
        $this->_table       = "tblPaper";
        $this->arrFieldsMap = array(
            "pid" => "pid",
            "title" => "title",
            "type" => "type",
            "frequency" => "frequency",
            "total_score" => "total_score",
            "total_question" => "total_question",
            "remark" => "remark",
            "operator" => "operator",
            "update_time" => "update_time",
            "create_time" => "create_time",
            "ext" => "ext",
        );
    }
}