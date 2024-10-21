<?php

class Service_Page_Schedule_Calendar_Export  extends Zy_Core_Service{
    public $weekName = [
        1 => "周一",
        2 => "周二",
        3 => "周三",
        4 => "周四",
        5 => "周五",
        6 => "周六",
        7 => "周日",
        0 => "周日",
    ];

    public $sts;
    public $ets;


    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $type   = empty($this->request['type']) ? "" : $this->request['type'];
        $id     = empty($this->request['value']) ? 0 : intval($this->request['value']);
        $this->sts    = empty($this->request['start']) ? 0 : strtotime($this->request['start']);
        $this->ets    = empty($this->request['end']) ? 0: strtotime($this->request['end']);

        if ($this->sts <= 0 || $this->ets <= 0 || $this->ets <= $this->sts) {
            throw new Zy_Core_Exception(405, "查询日期不正确, 请联系管理员");
        }

        $conds = array(
            sprintf("start_time >= %d", $this->sts),
            sprintf("end_time <= %d", $this->ets),
        );

        $lists = $lock = array();
        if ($type == "student" || $type == "teacher") {
            $serviceUser = new Service_Data_Profile();
            $userInfo = $serviceUser->getUserInfoByUid($id);
            if (empty($userInfo)) {
                throw new Zy_Core_Exception(405, "人员信息不存在或已被删除");
            }
            if ($type == "student") {
                $conds[] = sprintf("student_uid = %d", intval($id));
                $serviceData = new Service_Data_Curriculum();
                $lists = $serviceData->getListByConds($conds);
            } else {
                $conds[] = sprintf("teacher_uid = %d", intval($id));
                $serviceSchedule = new Service_Data_Schedule();
                $lists = $serviceSchedule->getListByConds($conds);
            }
        } else {
            $serviceGroup = new Service_Data_Group();
            $groupInfo = $serviceGroup->getGroupById($id);
            if (empty($groupInfo)) {
                throw new Zy_Core_Exception(405, "班级信息不存在");
            }
            $conds[] = sprintf("group_id = %d", intval($id));
            $serviceSchedule = new Service_Data_Schedule();
            $lists = $serviceSchedule->getListByConds($conds);
        }

        // 是否有锁的日程
        if ($type == "teacher") {
            $serviceLock = new Service_Data_Lock();
            $lock = $serviceLock->getListByUid($id, $this->sts, $this->ets);
        }

        $lists = $this->formatBase($lists, $lock, $type);
        if (empty($lists)) {
            throw new Zy_Core_Exception(405, sprintf("%s - %s 无课程信息", $this->request['start'], $this->request['end']));
        }

        $this->makeExcel($lists);
        
        exit;
    }

    // 格式化
    private function formatBase ($lists, $lock, $type) {
        if (empty($lists) && empty($lock)) {
            return array();
        }

        $teacherUids    = Zy_Helper_Utils::arrayInt($lists, "teacher_uid");
        $studentUids    = Zy_Helper_Utils::arrayInt($lists, "student_uid");
        $uids           = Zy_Helper_Utils::arrayInt($lists, "uid");
        $groupIds       = Zy_Helper_Utils::arrayInt($lists, "group_id");
        $subjectIds     = Zy_Helper_Utils::arrayInt($lists, "subject_id");
        $areaIds        = Zy_Helper_Utils::arrayInt($lists, "area_id");
        $roomIds        = Zy_Helper_Utils::arrayInt($lists, "room_id");

        $uids = array_unique(array_merge($teacherUids, $studentUids, $uids));

        $serviceSubject = new Service_Data_Subject();
        $subjectInfo = $serviceSubject->getListByConds(array('id in ('.implode(',', $subjectIds).')'));
        $subjectInfo = array_column($subjectInfo, null, 'id');

        $subjectParentIds = Zy_Helper_Utils::arrayInt($subjectInfo, "parent_id");
        $subjectParentInfos = $serviceSubject->getSubjectByIds($subjectParentIds);
        $subjectParentInfos = array_column($subjectParentInfos, null, 'id');

        $serviceUser = new Service_Data_Profile();
        $userInfos = $serviceUser->getListByConds(array('uid in ('.implode(',', $uids).')'));
        $userInfos = array_column($userInfos, null, 'uid');

        $serviceGroup = new Service_Data_Group();
        $groupInfos = $serviceGroup->getListByConds(array('id in ('.implode(",", $groupIds).')'));
        $groupInfos = array_column($groupInfos, null, 'id');

        $areaInfos = $roomInfos = array();
        if (!empty($roomIds)) {
            $serviceArea = new Service_Data_Area();
            $roomInfos = $serviceArea->getRoomListByConds(array('id in ('.implode(",", $roomIds).')'));
            $roomInfos = array_column($roomInfos, null, 'id');
        }
        if (!empty($areaIds)) {
            $serviceArea = new Service_Data_Area();
            $areaInfos = $serviceArea->getAreaListByConds(array('id in ('.implode(",", $areaIds).')'));
            $areaInfos = array_column($areaInfos, null, 'id');
        }

        
        $result = array();
        foreach ($lists as $key => $item) {
            if ($type == "student") {
                if (empty($userInfos[$item['student_uid']]['nickname'])) {
                    continue;
                }
                if (empty($userInfos[$item['teacher_uid']]['nickname'])) {
                    continue;
                }
                if (empty($subjectInfo[$item['subject_id']]['name'])) {
                    continue;
                }
                if (empty($groupInfos[$item['group_id']]['name'])) {
                    continue;
                }
                if (empty($subjectInfo[$item['subject_id']]['parent_id'])) {
                    continue;
                }
                $subjectParentId = $subjectInfo[$item['subject_id']]['parent_id'];
                if (empty($subjectParentInfos[$subjectParentId]['name'])) {
                    continue;
                }
            } else {
                if (empty($userInfos[$item['teacher_uid']]['nickname']) && empty($userInfos[$item['uid']]['nickname'])) {
                    continue;
                }
                if (isset($item['teacher_uid']) && empty($subjectInfo[$item['subject_id']]['name'])) {
                    continue;
                }
                if (isset($item['teacher_uid']) && empty($groupInfos[$item['group_id']]['name'])) {
                    continue;
                }
                if (empty($subjectInfo[$item['subject_id']]['parent_id'])) {
                    continue;
                }
                $subjectParentId = $subjectInfo[$item['subject_id']]['parent_id'];
                if (empty($subjectParentInfos[$subjectParentId]['name'])) {
                    continue;
                }
            }
            $tmp = array();
            $tmp['start']   = $item['start_time'];
            $tmp['end']     = $item['end_time'];
            $tmp["state"]   = $item["state"];
             // if ($item['state'] == Service_Data_Schedule::SCHEDULE_DONE) {
            //     $tmp['color'] = "#2a8041";    
            // }

            // 校区信息
            $areaName = "";
            $ext = empty($item['ext']) ? array() : json_decode($item['ext'], true);
            if (!empty($item['area_id']) && !empty($areaInfos[$item['area_id']]['name'])) {
                $areaName = $areaInfos[$item['area_id']]['name'];
                if (!empty($item['room_id']) && !empty($roomInfos[$item['room_id']]['name'])) {
                    $areaName = sprintf("%s(%s)", $areaName, $roomInfos[$item['room_id']]['name']);
                } else {
                    $areaName = sprintf("%s(%s)", $areaName, "无教室");
                }
                if (isset($ext['is_online']) && $ext['is_online'] == 1) {
                    $areaName = sprintf("%s(%s)", $areaName, "线上");
                }
            }
            $subjectName = sprintf("%s/%s", $subjectParentInfos[$subjectParentId]['name'], $subjectInfo[$item['subject_id']]['name']);
            $timespam = sprintf("%s-%s", date("H:i", $item['start_time']),date("H:i", $item['end_time']));
            if ($item["state"] == Service_Data_Schedule::SCHEDULE_DONE) {
                $timespam .= " (已消课)";
            } else {
                $timespam .= " (未消课)";
            }
            if ($type == "teacher") {
                $tmp['title'] = sprintf("%s\n%s\n%s\n%s\n", $timespam, $subjectName, $groupInfos[$item['group_id']]['name'], $areaName);  
            } else if ($type == "student"){
                $tmp['title'] = sprintf("%s\n%s\n%s\n%s\n", $timespam, $subjectName, $userInfos[$item['teacher_uid']]['nickname'], $areaName);
            } else if ($type == "group"){
                $tmp['title'] = sprintf("%s\n%s\n%s\n%s\n", $timespam, $subjectName, $userInfos[$item['teacher_uid']]['nickname'], $areaName);
            }

            $result[] = $tmp;            
        }

        if (!empty($lock)) {
            foreach ($lock as $item) {
                $result[] = array(
                    "title"     => "教师锁定时间",
                    "start"     => $item['start_time'],
                    "end"       => $item['end_time'],
                    "state"     => 3,
                    // "color"     => "#123456"
                );
            }
        }

        return $result;
    }


    private function makeExcel ($lists) {
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

        $result = $this->getHeader($lists);

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
                    $sheet->getStyle($item["merge"][0])
                        ->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)
                        ->getStartColor()->setRGB("77BFBF");
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
    public function getHeader (&$lists) {
        $cells = array("A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z");
        $hCells = array("00:00", "", "01:00", '', '02:00', '', '03:00','','04:00','','05:00','','06:00','', '07:00', '', '08:00', '', '09:00','','10:00','','11:00','','12:00','','13:00','','14:00','','15:00','','16:00','','17:00','','18:00','','19:00','','20:00','','21:00', "", "22:00", "", "23:00", "");
        $hCells2 = array("00:00", "00:30", "01:00", '01:30', '02:00', '02:30', '03:00','03:30','04:00','04:30','05:00','05:30','06:00','06:30', '07:00', '07:30', '08:00', '08:30', '09:00','09:30','10:00','10:30','11:00','11:30','12:00','12:30','13:00','13:30','14:00','14:30','15:00','15:30','16:00','16:30','17:00','17:30','18:00','18:30','19:00','19:30','20:00','20:30','21:00', "21:30", "22:00", "22:30", "23:00", "23:30");
        $wCells = array();
        for ($sts = $this->sts; $sts <= $this->ets; $sts+=86400) {
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
            $tV = $i == 0 ? "" : date("m月d日", $wCells[$i -1])."(". $this->weekName[date("w", $wCells[$i-1])] . ")";
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