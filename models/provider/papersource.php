<?php

class Dao_Papersource extends Zy_Core_Dao {

    public $arrFieldsMap;

    public function __construct() {
        $this->_dbName      = "zy_mapiv2";
        $this->_table       = "tblPapersource";
        $this->arrFieldsMap = array(
            "id" => "id",
            "paper_id" => "paper_id",
            "source_id" => "source_id",
        );
    }
}