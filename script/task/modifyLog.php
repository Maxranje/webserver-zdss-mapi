<?php
class modifyLog{

    public function execute($params) {
        $logPath = Zy_Helper_Config::getConfig('config')['log_path'];
        $logPath = ($logPath !== '') ? BASEPATH . $logPath : BASEPATH . 'logs/service.log';
        if (file_exists($logPath) ){
            rename($logPath, $logPath.".".date("Ymd", strtotime("-1days")));
        }

        touch($logPath);
        $ret = chmod($logPath, 0666);
        var_dump($ret);
    }
}