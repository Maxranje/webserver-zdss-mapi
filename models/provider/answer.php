<?php

class Dao_Answer extends Zy_Core_Dao {

    public $arrFieldsMap;
    public $simpleFieldsMap;

    public function __construct() {
        $this->_dbName      = "zy_mapiv2";
        $this->_table       = "tblAnswer";
        $this->arrFieldsMap = array(
            "id" => "id",
            "qid" => "qid",
            "parent_id" => "parent_id",
            "type" => "type",
            "content" => "content",
            "is_correct" => "is_correct",
            "operator" => "operator",
            "update_time" => "update_time",
            "create_time" => "create_time",
            "ext" => "ext",
        );
        $this->simpleFieldsMap = array(
            "id" => "id",
            "qid" => "qid",
            "parent_id" => "parent_id",
            "type" => "type",
            "is_correct" => "is_correct",
            "operator" => "operator",
            "update_time" => "update_time",
            "create_time" => "create_time",
        );        
    }
}