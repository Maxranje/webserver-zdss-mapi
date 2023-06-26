<?php

class Dao_Capital extends Zy_Core_Dao {

    public $arrFieldsMap = array();

    public function __construct() {
        $this->_dbName      = "zy_mapi";
        $this->_table       = "tblCapital";
        $this->arrFieldsMap = array(
            "id"   => 'id',
            "uid"  => "uid" ,
            "column_id" => "column_id",
            "group_id" => "group_id",
            "type" => "type",
            "category" => "category",
            "operator" => "operator",
            "capital" => "capital",
            "capital_remark" => "capital_remark",
            "create_time"  => "create_time" , 
            "update_time"  => "update_time" , 
            "ext"  => "ext" , 
        );
    }
}