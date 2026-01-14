<?php

// auth验证
class Zy_Helper_Authtoken {  
    
    const TOKEN_RULER = "%s_%s";



    public static function buildToken($uid) {
        return md5(sprintf(self::TOKEN_RULER, $uid, date("Ymd")));
    }

    public static function validateToken($uid) {
        if (empty($_SERVER["Authorization"])) {
            return false; 
        }
        $args = explode(" ", $_SERVER["Authorization"]);
        if (!empty($args) && is_array($args) && count($args) == 2) {
            return $args[0] == "Bearer" && md5(sprintf(self::TOKEN_RULER, $uid, date("Ymd"))) == $args[1];
        }
        return false;
    }

}