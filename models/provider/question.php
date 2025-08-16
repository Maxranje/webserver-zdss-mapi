<?php

class Dao_Question extends Zy_Core_Dao {

    public $arrFieldsMap;

    public function __construct() {
        $this->_dbName      = "zy_mapiv2";
        $this->_table       = "tblQuestion";
        $this->arrFieldsMap = array(
            "qid" => "qid",
            "premeta_id" => "premeta_id",
            "is_group" => "is_group",
            "content" => "content",
            "highlight_id" => "highlight_id",
            "type" => "type",
            "level" => "level",
            "score" => "score",
            "frequency" => "frequency",
            "operator" => "operator",
            "update_time" => "update_time",
            "create_time" => "create_time",
            "ext" => "ext",
        );
    }
}