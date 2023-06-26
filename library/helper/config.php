<?php
/**
 *  加载系统配置和工程配置日志目录, 并提供相应
 *  系统配置  SYSPATH/config/config.php
 *  工程配置  APPPATH/config/config.php
 *
 */

class Zy_Helper_Config {

    // 系统文件
	private static $config = array();

    // 程序文件
    private static $appConfig = array();

    /**
     * 读取配置, 读取成功返回值
     *
     * @param  mixed   键
     * @return mixed   值
     */
	public static function getConfig ($confName)
	{
        if (empty($confName))
        {
            return NULL;
        }

		if (empty(self::$config[$confName])) {
			self::$config[$confName] = self::load_config (SYSPATH, $confName);
		}

        return !empty(self::$config[$confName]) ? self::$config[$confName] : NULL;
	}

    /**
     * 读取配置, 读取成功返回值
     *
     * @param  mixed   键
     * @return mixed   值
     */
	public static function getAppConfig ($confName)
	{
         if (empty($confName))
        {
            return NULL;
        }

		if (empty(self::$appConfig[$confName])) {
			self::$appConfig[$confName] = self::load_config (BASEPATH, $confName);
		}

        return !empty(self::$appConfig[$confName]) ? self::$appConfig[$confName] : NULL;
	}

    /**
     * 从配置文件中读取配置选项, 工程配置与系统配置相同则使用系统配置
     *
     * @return void
     */
    private static function load_config ($basePath, $confPath)
    {
        $configure = array();

        // load system config
        $config_path = $basePath . 'config/' .$confPath. '.php';
        if (!file_exists($config_path))
        {
            return $configure;
        }

        $configure = require($config_path);

        if (empty($configure) || !is_array($configure))
        {
            trigger_error ('Error] config error [Detail] config not set or empty');
        }
        
        return $configure;
    }
}
