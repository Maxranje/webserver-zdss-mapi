<?php

class Dao_ExamAnswer extends Zy_Core_Dao {

    public $arrFieldsMap;
    public $simpleFieldsMap;

    public function __construct() {
        $this->_dbName      = "zy_mapiv2";
        $this->_table       = "tblExamAnswer";
        $this->arrFieldsMap = array(
            "id" => "id",
            "exam_id" => "exam_id",
            "pid" => "pid",
            "student_uid" => "student_uid",
            "type" => "type",
            "qid" => "qid",
            "answer_id" => "answer_id",
            "is_correct" => "is_correct",
            "score" => "score",
            "answer_content" => "answer_content",
            "review_content" => "review_content",
            "spend_time" => "spend_time",
            "update_time" => "update_time",
            "ext" => "ext",
        );
        $this->simpleFieldsMap = array(
            "id" => "id",
            "exam_id" => "exam_id",
            "pid" => "pid",
            "student_uid" => "student_uid",
            "type" => "type",
            "qid" => "qid",
            "answer_id" => "answer_id",
            "is_correct" => "is_correct",
            "score" => "score",
            "spend_time" => "spend_time",
            "update_time" => "update_time",
            "ext" => "ext",
        );        
    }
}