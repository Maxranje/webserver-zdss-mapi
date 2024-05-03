<?php

class Dao_User extends Zy_Core_Dao {

    public function __construct() {
        $this->_dbName      = "zy_mapiv2";
        $this->_table       = "tblUser";
        $this->arrFieldsMap = array(
            "uid"  => "uid" , 
            "type"  => "type" , 
            "name"  => "name" , 
            "state" => "state",
            "nickname"  => "nickname" , 
            "passport" => "passport",
            "phone"  => "phone" , 
            "avatar" => "avatar",
            "bpid" => "bpid",
            "sop_uid" => "sop_uid",
            "school"  => "school" , 
            "graduate"  => "graduate" , 
            "balance"   => "balance",
            "sex"  => "sex" , 
            "create_time"  => "create_time" , 
            "update_time"  => "update_time" , 
            "ext"  => "ext" , 
        );
    }
}