<?php

class Dao_Answer extends Zy_Core_Dao {

    public $arrFieldsMap;

    public function __construct() {
        $this->_dbName      = "zy_mapiv2";
        $this->_table       = "tblAnswer";
        $this->arrFieldsMap = array(
            "id" => "id",
            "qid" => "qid",
            "content" => "content",
            "type" => "type",
            "is_correct" => "is_correct",
            "score" => "score",
            "operator" => "operator",
            "update_time" => "update_time",
            "create_time" => "create_time",
            "ext" => "ext",
        );
    }
}