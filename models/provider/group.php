<?php

class Dao_Group extends Zy_Core_Dao {

    public function __construct() {
        $this->_dbName      = "zy_mapiv2";
        $this->_table       = "tblGroup";
        $this->arrFieldsMap = array(
            "id" => "id",
            "name" => "name",
            "descs" => "descs",
            "state" => "state",
            "area_operator" => "area_operator",
            "update_time" => "update_time",
            "create_time" => "create_time",
            "ext" => "ext",
        );
    }
}