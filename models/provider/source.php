<?php

class Dao_Source extends Zy_Core_Dao {

    public $arrFieldsMap;

    public function __construct() {
        $this->_dbName      = "zy_mapiv2";
        $this->_table       = "tblSource";
        $this->arrFieldsMap = array(
            "id" => "id",
            "name" => "name",
            "parent_id" => "parent_id",
            "update_time" => "update_time",
            "create_time" => "create_time",
            "ext" => "ext",
        );
    }
}