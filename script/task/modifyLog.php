<?php
class modifyLog{

    public function execute($params) {
        $logPath = Zy_Helper_Config::getConfig('config')['log_path'];
        $logfile = Zy_Helper_Config::getConfig('config')['log_file'];

        $logPath = ($logPath !== '') ? $logPath : BASEPATH . '../log';
        $logfile = $logPath . DIRECTORY_SEPARATOR . ($logfile !== '' ? $logfile : "service.log");

        if (file_exists($logfile) ){
            rename($logfile, $logfile.".".date("Ymd", strtotime("-1days")));
        }

        touch($logfile);
        $ret = chmod($logfile, 0666);
        var_dump($ret);
    }
}