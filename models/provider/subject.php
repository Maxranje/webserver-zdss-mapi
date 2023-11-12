<?php

class Dao_Subject extends Zy_Core_Dao {

    public function __construct() {
        $this->_dbName      = "zy_mapiv2";
        $this->_table       = "tblSubject";
        $this->arrFieldsMap = array(
            "id" => "id",
            "name" => "name",
            "descs" => "descs",
            "identify" => "identify",
            "parent_id" => "parent_id",
            "update_time" => "update_time",
            "create_time" => "create_time",
            "ext" => "ext",
        );
    }
}