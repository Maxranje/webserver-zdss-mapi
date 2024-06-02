<?php

class Dao_Review extends Zy_Core_Dao {

    public $arrFieldsMap = array();

    public function __construct() {
        $this->_dbName      = "zy_mapiv2";
        $this->_table       = "tblReview";
        $this->arrFieldsMap = array(
            "id" => "id",
            "type" => "type",
            "state" => "state",
            "uid" => "uid",
            "rop_uid" => "rop_uid",
            "sop_uid" => "sop_uid",
            "work_id" => "work_id",
            "remark" => "remark",
            "update_time" => "update_time",
            "create_time" => "create_time",
            "ext" => "ext",
        );
    }
}