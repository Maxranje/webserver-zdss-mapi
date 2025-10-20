<?php

class Dao_Meta extends Zy_Core_Dao {

    public $arrFieldsMap;

    public function __construct() {
        $this->_dbName      = "zy_mapiv2";
        $this->_table       = "tblMeta";
        $this->arrFieldsMap = array(
            "id" => "id",
            "content" => "content",
            "meta_type" => "meta_type",
            "operator" => "operator",
            "update_time" => "update_time",
            "ext" => "ext",
        );
    }
}