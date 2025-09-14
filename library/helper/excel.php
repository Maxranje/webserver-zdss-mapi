<?php


class Zy_Helper_Excel {


    public static function makeExcel ($lists) {
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', 0);
        require SYSPATH . "/PHPExcel/PHPExcel.php";

        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");;
        header('Content-Disposition:attachment;filename="export.xls"');
        header("Content-Transfer-Encoding:binary");

        $objPHPExcel = new PHPExcel();
        $objPHPExcel->setActiveSheetIndex(0);
        $objPHPExcel->getDefaultStyle()->getAlignment()->setHorizontal("left")->setVertical("top")->setWrapText(true);
        $sheet = $objPHPExcel->getActiveSheet();
        $sheet->freezePane("B2");
        $sheet->getStyle('1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB("C5BCBC");
        $sheet->getStyle('A')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB("C5BCBC");

        $result = self::getHeader($lists, array());

        foreach ($result as $item) {
            foreach ($item as $t) {
                $sheet->getCell($t["position"])->setValue($t["value"]);
                if (isset($t['positionKey'])) {
                    $sheet->getColumnDimension($t["positionKey"])->setWidth(35);
                }
                if (!empty($t['positionVir'])) {
                    $sheet->getRowDimension($t['positionVir'])->setRowHeight(25);
                }
            }
        }

        foreach ($lists as $item) {
            if (empty($item['merge'])) {
                continue;
            }
            if (count($item["merge"]) <= 1) {
                $sheet->getCell($item["merge"][0])->setValue($item["title"]);
            } else {
                usort($item["merge"], function ($a, $b) {
                    return strnatcasecmp($a, $b);
                });
                $item["merge"] = array_values($item["merge"]);
                $tmp = $item["merge"][0] . ":" . $item["merge"][count($item["merge"]) - 1];
                $values = array($item["title"]);
                if (!empty($item["title_map"])) {
                    $values = array_merge($values, $item["title_map"]);
                }
                $sheet->mergeCells($tmp)->setCellValue($item["merge"][0], implode("\n", $values));
                if ($item['state'] == Service_Data_Schedule::SCHEDULE_DONE) {
                    $sheet->getStyle($item["merge"][0])
                        ->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)
                        ->getStartColor()->setRGB("7FD26A");
                } else if ($item['state'] == Service_Data_Schedule::SCHEDULE_ABLE) {
                    $color = "77BFBF";
                    if (!empty($item["noOrderColor"])) {
                        $color = "CD9B1D";
                    }
                    $sheet->getStyle($item["merge"][0])
                        ->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)
                        ->getStartColor()->setRGB($color);
                } else if ($item['state'] == 3) {
                    $sheet->getStyle($item["merge"][0])
                        ->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)
                        ->getStartColor()->setRGB("BBBBBB");
                }
                
            }
        }

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;
    
    }

    // 构造excel数据
    public static function getHeader (&$lists, $params) {
        return array();
        $cells = array("A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z");
        $hCells = array("00:00", "", "01:00", '', '02:00', '', '03:00','','04:00','','05:00','','06:00','', '07:00', '', '08:00', '', '09:00','','10:00','','11:00','','12:00','','13:00','','14:00','','15:00','','16:00','','17:00','','18:00','','19:00','','20:00','','21:00', "", "22:00", "", "23:00", "");
        $hCells2 = array("00:00", "00:30", "01:00", '01:30', '02:00', '02:30', '03:00','03:30','04:00','04:30','05:00','05:30','06:00','06:30', '07:00', '07:30', '08:00', '08:30', '09:00','09:30','10:00','10:30','11:00','11:30','12:00','12:30','13:00','13:30','14:00','14:30','15:00','15:30','16:00','16:30','17:00','17:30','18:00','18:30','19:00','19:30','20:00','20:30','21:00', "21:30", "22:00", "22:30", "23:00", "23:30");
        $wCells = array();
        for ($sts = $params["sts"]; $sts <= $params['ets']; $sts+=86400) {
            $wCells[] = $sts;
        }

        // 计算出头
        $headerCells = array();
        $len = count($wCells) - count($cells) + 1;
        if ($len > 0) {
            $headerCells = $cells;
            for ($i =0 ; $i < count($cells); $i++) {
                for ($j =0 ; $j < count($cells); $j++) {
                    $headerCells[] = $cells[$i] . $cells[$j];
                    $len -- ;
                    if ($len <= 0) {
                        break;
                    }
                }
                if ($len <= 0) {
                    break;
                }
            }
        } else {
            $headerCells = array_slice($cells, 0, $len);
        }

        // 构造数据
        $result = array();
        foreach ($headerCells as $i => $k) {
            $tV = $i == 0 ? "" : date("m月d日", $wCells[$i -1])."(". $params["week"][date("w", $wCells[$i-1])] . ")";
            for ($j = 0; $j < count($hCells); $j++) {
                if (!isset($result[$i][$j])) {
                    $result[$i][$j] = array(
                        "value" =>  "",
                        "start" => 0,
                        "end" => 0,
                        "position" => $k . strval($j+1),
                    );
                }
                if ($j == 0 && $i == 0) {
                    continue;
                }
                if ($j == 0) {
                    $result[$i][$j]["value"] = $tV;
                    continue;
                }
                if ($i == 0) {
                    $result[$i][$j]["value"] = $hCells[$j - 1];
                    continue;
                }

                $result[$i][$j]["positionKey"] = $k;
                $result[$i][$j]["positionVir"] = $j;
                $result[$i][$j]['start'] = strtotime(date("Y-m-d ", $wCells[$i -1]) . $hCells2[$j - 1]);
                $result[$i][$j]['end'] = strtotime(date("Y-m-d ", $wCells[$i -1]) . $hCells2[$j]);
            }
        }


        $map = array();
        foreach ($lists as $index => &$item) {
            $item["merge"] = array();
            foreach ($result as $l) {
                foreach ($l as $c) {
                    $flag = false;
                    if (!empty($c['start']) && !empty($c['end'])) {
                        if ($c['start'] > $item['start'] && $c['start'] < $item['end']) {
                            $flag = true;
                        }
                        if ($c['end'] > $item['start'] && $c['end'] < $item['end']) {
                            $flag = true;
                        }
                        if ($c['start'] < $item['start'] && $c['end'] > $item['end']) {
                            $flag = true;
                        }
                        if ($c['start'] == $item['start'] || $c['end'] == $item['end']) {
                            $flag = true;
                        }
                    }
                    if ($flag) {
                        $item['merge'][] = $c["position"];
                    }
                }
            }
        }

        // 合并单元格
        if (count($lists) > 1) {
            for ($i = 0; $i < count($lists) - 1; $i++) {
                if (empty($lists[$i]["merge"])) {
                    continue;
                }
                for ($j = $i+1; $j < count($lists); $j++) {
                    if (!empty(array_intersect($lists[$i]["merge"], $lists[$j]["merge"]))) {
                        $lists[$i]['title_map'][] = $lists[$j]["title"];
                        $lists[$i]["end"] = $lists[$j]["end"];
                        $lists[$i]["merge"] = array_merge($lists[$i]["merge"], $lists[$j]["merge"]);
                        $lists[$i]["merge"] = array_values(array_unique($lists[$i]["merge"]));
                        unset($lists[$j]);
                        $lists = array_values($lists);
                    }
                }
            }
        }

        return $result;
    }
}