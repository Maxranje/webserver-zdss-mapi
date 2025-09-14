<?php

class Dao_Tag extends Zy_Core_Dao {

    public function __construct() {
        $this->_dbName      = "zy_mapiv2";
        $this->_table       = "tblTag";
        $this->arrFieldsMap = array(
            "id" => "id",
            "title" => "title",
            "description" => "description",
            "parent_id" => "parent_id",
            "update_time" => "update_time",
            "create_time" => "create_time",
            "ext" => "ext",
        );
    }
}