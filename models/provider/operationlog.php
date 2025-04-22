<?php

class Dao_Operationlog extends Zy_Core_Dao {

    public function __construct() {
        $this->_dbName      = "zy_mapiv2";
        $this->_table       = "tblOperationLog";
        $this->arrFieldsMap = array(
            "id" => "id",
            "uid" => "uid",
            "point" => "point",
            "work_id" => "work_id",
            "original_data" => "original_data",
            "current_data" => "current_data",
            "update_time" => "update_time",
            "create_time" => "create_time",
            "ext" => "ext",
        );
    }
}

