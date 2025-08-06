<?php
define('APP_NAME',  'mapi');
define('ENV',       'development');
define('BASEPATH',  dirname(__FILE__).DIRECTORY_SEPARATOR);
define('SYSPATH',   BASEPATH . 'library/');
define('VIEWPATH',  BASEPATH . 'public/mis');

# init error reporting by env
switch (ENV) {
	case 'development':
		ini_set('display_errors', 1);
		error_reporting(-1);

        # reset path
        define('LOGPATH',   BASEPATH . '../../../log');
        define('HOSTNAME',   "http://127.0.0.1:8060/");        
		break;

	case 'production':
		ini_set('display_errors', 1);
		error_reporting (E_ERROR & E_USER_WARNING);

        # reset path
        define('LOGPATH',   BASEPATH . '../log');
        define('HOSTNAME',  "http://zdss.cn/");        
		break;

	default:
		exit(1);
}

require_once SYSPATH . 'autoload/autoload.php';
Zy_Core_Bootstrap::getInstance()->run();