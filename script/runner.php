<?php
define('BASEPATH',  dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR);
define('SYSPATH',   BASEPATH . 'library/');
require_once SYSPATH . 'autoload/autoload.php';

if (count($argv) < 2) {
    echo "参数不足\n";
    exit;
}

require_once ($argv[1] . ".php");

$class = ucfirst($argv[1]);
$a = new $class();

$params = array();
if (!empty($argv[2])) {
    $params = $argv[2];
}
$a->execute($params);
exit;

