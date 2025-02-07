<?php

class Dao_ApackageConfirm extends Zy_Core_Dao {

    public $arrFieldsMap;

    public function __construct() {
        $this->_dbName      = "zy_mapiv2";
        $this->_table       = "tblApackageConfirm";
        $this->arrFieldsMap = array(
            "id" => "id",
            "abroadplan_id" => "abroadplan_id",
            "apackage_id" => "apackage_id",
            "content" => "content",
            "operator" => "operator",
            "update_time" => "update_time",
            "create_time" => "create_time",
        );
    }
}