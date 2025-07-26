<?php
define('APP_NAME',  'mapi');
define('ENV',       'development');

# init error reporting by env
switch (ENV) {
	case 'development':
		ini_set('display_errors', 1);
		error_reporting(-1);
		break;

	case 'production':
		ini_set('display_errors', 1);
		error_reporting (E_ERROR & E_USER_WARNING);
		break;

	default:
		exit(1);
}

# set file path
define('BASEPATH',  dirname(__FILE__).DIRECTORY_SEPARATOR);
define('SYSPATH',   BASEPATH . 'library/');
define('VIEWPATH',  BASEPATH . 'public/sdk');
if (ENV == "development") {
    define('LOGPATH',   BASEPATH . '../../log');
    define('HOSTNAME',   "http://127.0.0.1:8060/");
} else {
    define('LOGPATH',   BASEPATH . '../log');
    define('HOSTNAME',  "http://zdss.cn/");
}

require_once SYSPATH . 'autoload/autoload.php';
Zy_Core_Bootstrap::getInstance()->run();