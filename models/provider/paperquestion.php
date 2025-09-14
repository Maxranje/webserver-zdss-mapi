<?php

class Dao_PaperQuestion extends Zy_Core_Dao {

    public $arrFieldsMap;

    public function __construct() {
        $this->_dbName      = "zy_mapiv2";
        $this->_table       = "tblPaperQuestion";
        $this->arrFieldsMap = array(
            "id" => "id",
            "pid" => "pid",
            "qid" => "qid",
            "score" => "score",
            "update_time" => "update_time",
            "ext" => "ext",
        );
    }
}