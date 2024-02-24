<?php
class Zy_Helper_Utils {

    public static function exportExcel($fileName, $tileArray = [], $dataArray = [])
    {
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', 0);
        require SYSPATH . "/phpexcel/PHPExcel.php";

        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");;
        header('Content-Disposition:attachment;filename="'.$fileName.'.xls"');
        header("Content-Transfer-Encoding:binary");

        $objPHPExcel = new PHPExcel();
        $objPHPExcel->setActiveSheetIndex(0);

        $count = count($dataArray);
        for ($i = 2; $i <= $count+1; $i++) { 
            $objPHPExcel->getActiveSheet()->fromArray($dataArray);
        }
        $objPHPExcel->createSheet();

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;
    }

    public static function exportExcelSimple($fileName, $tileArray = [], $dataArray = [])
    {
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', 0);
        ob_end_clean();
        ob_start();
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");;
        header('Content-Disposition:attachment;filename="'.$fileName.'.xls"');
        header("Content-Transfer-Encoding:binary");
        
        $fp = fopen('php://output', 'w');
        fwrite($fp, chr(0xEF) . chr(0xBB) . chr(0xBF));// 转码 防止乱码(比如微信昵称)
        fputcsv($fp, $tileArray);
        $index = 0;
        foreach ($dataArray as $item) {
            if ($index == 20000) {
                $index = 0;
                ob_flush();
                flush();
            }
            $index++;
            fputcsv($fp, $item);
        }
        ob_flush();
        flush();
        ob_end_clean();
        exit;
    }

    // 数组内数据做为整数
    public static function arrayInt ($arr, $key = "") {
        if (empty($arr) || !is_array($arr)) {
            return array();
        }
        $result = array();
        if (empty($key)) {
            foreach ($arr as $item) {
                $result[intval($item)] = intval($item);
            }
        } else {
            foreach ($arr as $item) {
                if (!isset($item[$key])) {
                    continue;
                }
                $result[intval($item[$key])] = intval($item[$key]);
            }
        }
        return array_values($result);
    }

    /** 
     * 格式化时长
     * @param int $secondParam 传入秒数
     * @return string 返回时长，格式为 1小时3分20秒 
     */
    public static function formatDurationForTime($secondParam) {
        $durationSec = (int) $secondParam;
        $hour = floor($durationSec / 3600);
        $hourSecond = $durationSec - $hour * 3600;
        $minute = floor($hourSecond / 60);
        $duration = '';
        if ($hour > 0) {
            $duration = $hour . ' Hour ';
        }
        if ($minute > 0) {
            $duration .= $minute . ' Min ';
        }
        if (empty($duration)) {
            $duration = "0 Hour";
        }
        return $duration;
    }

    public static function checkStr($str) {
        $str = trim($str);
        preg_match_all('/[\x{4e00}-\x{9fa5}a-zA-Z0-9,]+/u',$str,$result);
        if (empty($result[0][0]) || mb_strlen($str, "utf-8") != mb_strlen($result[0][0], "utf-8")) {
            return false;
        }
        return true;
    }

    public static function autoID ($k1, $k2) {
        $end = time() - strtotime(date("Ymd"));
        $day = date("Ymd");
        return sprintf("%s-%s-%s-%s", $k1, $k2, $day,$end);
    }

    public static function FloadtoStringNoRound ($number) {
        $numberStr = strval($number);
        $index = strpos($numberStr, ".");
        if ($index === false) {
            return $numberStr.".00";
        }   
        $len = $index + 5 >= strlen($numberStr) ? strlen($numberStr) : $index+5;
        return substr($numberStr, 0, $len);

    }
}