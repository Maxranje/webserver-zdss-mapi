<?php

class Dao_PaperSource extends Zy_Core_Dao {

    public $arrFieldsMap;

    public function __construct() {
        $this->_dbName      = "zy_mapiv2";
        $this->_table       = "tblPaperSource";
        $this->arrFieldsMap = array(
            "id" => "id",
            "pid" => "pid",
            "source_id" => "source_id",
            "update_time" => "update_time",
        );
    }
}