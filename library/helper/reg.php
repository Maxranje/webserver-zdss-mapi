<?php

/**
 *  全局存储
 *
 */

class Zy_Helper_Reg {

	private static $data;

	// 设置
	public static function set ($key, $value)
	{
        self::$data[$key] = $value;
	}

    // 归还
	public static function get ($key, $default = null)
	{
        return !isset(self::$data[$key]) ? $default : self::$data[$key];
	}
}