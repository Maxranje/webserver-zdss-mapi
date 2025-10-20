<?php

class Dao_QuestionTag extends Zy_Core_Dao {

    public function __construct() {
        $this->_dbName      = "zy_mapiv2";
        $this->_table       = "tblQuestionTag";
        $this->arrFieldsMap = array(
            "id" => "id",
            "qid" => "qid",
            "tag_id" => "tag_id",
            "level" => "level",
            "update_time" => "update_time",
            "ext" => "ext",
        );
    }
}