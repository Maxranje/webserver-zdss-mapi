<?php

class Dao_Question extends Zy_Core_Dao {

    public $arrFieldsMap;
    public $simpleFieldsMap;

    public function __construct() {
        $this->_dbName      = "zy_mapiv2";
        $this->_table       = "tblQuestion";      
        $this->arrFieldsMap = array(
            "qid" => "qid",
            "type" => "type",
            "state" => "state",
            "level" => "level",
            "subject_id" => "subject_id",
            "content" => "content",
            "explan" => "explan",
            "description" => "description",
            "parent_id" => "parent_id",
            "is_coll" => "is_coll",
            "pre_meta_id" => "pre_meta_id",
            "light_id" => "light_id",
            "score" => "score",
            "operator" => "operator",
            "update_time" => "update_time",
            "create_time" => "create_time",
            "ext" => "ext",
        );

        $this->simpleFieldsMap = array(
            "qid" => "qid",
            "type" => "type",
            "level" => "level",
            "state" => "state",
            "subject_id" => "subject_id",
            "content" => "content",
            "description" => "description",
            "parent_id" => "parent_id",
            "is_coll" => "is_coll",
            "pre_meta_id" => "pre_meta_id",
            "light_id" => "light_id",
            "score" => "score",
            "operator" => "operator",
            "update_time" => "update_time",
            "create_time" => "create_time",
        );        
    }
}