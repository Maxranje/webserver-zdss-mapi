<?php

class Dao_User extends Zy_Core_Dao {

    public function __construct() {
        $this->_dbName      = "zy_mapi";
        $this->_table       = "tblUser";
        $this->arrFieldsMap = array(
            "uid"  => "uid" , 
            "type"  => "type" , 
            "name"  => "name" , 
            "state" => "state",
            "nickname"  => "nickname" , 
            "phone"  => "phone" , 
            "avatar" => "avatar",
            "birthplace" => "birthplace",
            "school"  => "school" , 
            "graduate"  => "graduate" , 
            "sex"  => "sex" , 
            "student_capital" => "student_capital",
            "teacher_capital" => "teacher_capital",
            "create_time"  => "create_time" , 
            "update_time"  => "update_time" , 
            "ext"  => "ext" , 
        );
    }
}