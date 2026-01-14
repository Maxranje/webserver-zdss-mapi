<?php

class Dao_QuestionSource extends Zy_Core_Dao {

    public $arrFieldsMap;

    public function __construct() {
        $this->_dbName      = "zy_mapiv2";
        $this->_table       = "tblQuestionSource";
        $this->arrFieldsMap = array(
            "id" => "id",
            "qid" => "qid",
            "source_id" => "source_id",
            "update_time" => "update_time",
        );
    }
}