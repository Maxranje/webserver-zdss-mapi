<?php
define('BASEPATH',  dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR);
define('SYSPATH',   BASEPATH . 'library/');
require_once SYSPATH . 'autoload/autoload.php';

$opt = getopt('t:c:m:p');
if (empty($opt["c"]) || empty($opt['t'])) {
    echo "参数不足\n";
    exit;
}

require_once ($opt['t']."/".$opt['c'] . ".php");
if (!class_exists(ucfirst($opt["c"]))) {
    echo "类不存在\n";
    exit;
}

$class = ucfirst($opt["c"]);
$a = new $class();

$method = "execute";
if (!empty($opt["m"])) {
    $method = $opt["m"];
}

if (!method_exists($a, $method)) {
    echo "方法不存在\n";
    exit;
}

$params = array();
if (!empty($opt["p"])) {
    $params = $opt["p"];
}

$a->$method($params);
exit;

