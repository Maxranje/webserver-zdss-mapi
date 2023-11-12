<?php

class Dao_Claszemap extends Zy_Core_Dao {

    public function __construct() {
        $this->_dbName      = "zy_mapiv2";
        $this->_table       = "tblClaszemap";
        $this->arrFieldsMap = array(
            "id" => "id",
            "cid" => "cid",
            "bpid" => "bpid",
            "subject_id" => "subject_id",
            "price" => "price",
            "update_time" => "update_time",
            "create_time" => "create_time",
            "ext" => "ext",
        );
    }
}