<?php
class Zy_Helper_Utils {

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
        $day = floor($durationSec / 86400);
        $hourSecond = $durationSec - $day * 86400;
        $hour = floor($durationSec / 3600);
        $hourSecond = $durationSec - $hour * 3600;
        $minute = floor($hourSecond / 60);
        $hourSecond = $durationSec - $minute * 60;
        $second = $hourSecond;
        $duration = '';
        if ($day > 0) {
            $duration = $day . ' d ';
        }
        if ($hour > 0) {
            $duration .= $hour . ' h ';
        }
        if ($minute > 0) {
            $duration .= $minute . ' m ';
        }
        if ($second > 0) {
            $duration .= $second . " s ";
        }
        if (empty($duration)) {
            $duration = "0 h";
        }
        return $duration;
    }

    public static function checkStr($str, $min = 0, $max = 999) {
        $str = trim($str);
        preg_match_all('/[\x{4e00}-\x{9fa5}a-zA-Z0-9,]+/u',$str,$result);
        if (empty($result[0][0]) || mb_strlen($str, "utf-8") != mb_strlen($result[0][0], "utf-8")) {
            return false;
        }
        if (strlen($str) < $min || strlen($str) > $max) {
            return false;
        }        
        return true;
    }

    public static function checkStrictStr($str, $min = 6, $max = 20) {
        $str = trim($str);
        preg_match_all('/[a-zA-Z0-9,]+/u',$str,$result);
        if (empty($result[0][0]) || mb_strlen($str, "utf-8") != mb_strlen($result[0][0], "utf-8")) {
            return false;
        }
        if (strlen($str) < $min || strlen($str) > $max) {
            return false;
        }        
        return true;
    } 
    
    /**
     * 验证字符串：检查长度范围、检测SQL注入风险，同时支持中文字符
     * 
     * @param string $str 需要检测的字符串
     * @param int $minLength 最小长度限制
     * @param int $maxLength 最大长度限制
     * @return bool 验证通过返回true，否则返回false
     */
    public static function validateString($str, $minLength = 0, $maxLength = 10000) {
        // 检查输入类型
        if (!is_string($str)) {
            return false;
        }
        // 检查字符串长度（中文字符按单个字符计算）
        $strLength = mb_strlen($str, 'UTF-8');
        if ($strLength < $minLength || $strLength > $maxLength) {
            return false;
        }
        
        // 检测潜在的SQL注入模式
        $sqlInjectionPatterns = [
            '/union\s+select/i',
            '/insert\s+into/i',
            '/update\s+.*set/i',
            '/delete\s+from/i',
            '/drop\s+(table|database)/i',
            '/alter\s+table/i',
            '/truncate\s+table/i',
            '/exec\s*\(/i',
            '/xp_cmdshell/i',
            '/declare\s+.*@/i',
            '/select\s+.*from/i',
            '/\b(or|and)\b\s+.*=.*--/i',
            '/\'.*--/'
        ];
        
        foreach ($sqlInjectionPatterns as $pattern) {
            if (preg_match($pattern, $str)) {
                return false;
            }
        }
        
        return true;
    }    

    public static function validateStringHttp($url) {
        $pattern = "^(http?|https?)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,4}\/?([^\s<>\#%\"]{0,2000}?)$";
        return preg_match("/$pattern/", $url) === 1;
    }
     

    public static function autoID ($k1, $k2 = "") {
        $end = time() - strtotime(date("Ymd"));
        $day = date("Ymd");
        if (empty($k2)) {
            return sprintf("%s-%s-%s", $k1, $day,$end);
        }
        return sprintf("%s-%s-%s-%s", $k1, $k2, $day,$end);
    }

    public static function FloadtoStringNoRound ($number) {
        if ($number < 0.01) {
            return 0;
        }
        $numberStr = strval($number);
        $index = strpos($numberStr, ".");
        if ($index === false) {
            return $numberStr.".00";
        }   
        $len = $index + 5 >= strlen($numberStr) ? strlen($numberStr) : $index+5;
        return substr($numberStr, 0, $len);
    }
}