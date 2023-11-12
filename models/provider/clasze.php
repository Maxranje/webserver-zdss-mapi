<?php

class Dao_Clasze extends Zy_Core_Dao {

    public function __construct() {
        $this->_dbName      = "zy_mapiv2";
        $this->_table       = "tblClasze";
        $this->arrFieldsMap = array(
            "id" => "id",
            "name" => "name",
            "identify" => "identify",
            "update_time" => "update_time",
            "create_time" => "create_time",
            "ext" => "ext",
        );
    }
}