<?php

class Service_Page_Area_Details extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $areaId     = empty($this->request['area_id']) ? 0 : intval($this->request['area_id']);
        $dateTime   = empty($this->request['datetime']) ? 0 : intval($this->request['datetime']);
        $isExport   = empty($this->request['is_export']) ? false : true;

        if ($areaId <= 0 && $dateTime <= 0 ) {
            return array();
        }

        if ($areaId <= 0 || $dateTime <= 0 ) {
            throw new Zy_Core_Exception(405, "操作失败, 校区或时间必须进行选择");
        }

        $serviceArea = new Service_Data_Area();
        $roomInfos = $serviceArea->getRoomListByAid($areaId);
        if (empty($roomInfos)) {
            throw new Zy_Core_Exception(405, "操作失败, 该校区下暂时没有教室");
        }
        $roomInfos = array_column($roomInfos, null, "id");

        $sts = $dateTime + (7 * 3600);
        $ets = $dateTime + (21 * 3600);

        $conds = array(
            sprintf("area_id = %d", $areaId),
            sprintf("start_time >= %d", $sts),
            sprintf("end_time <= %d", $ets),
            sprintf("state = %d", Service_Data_Schedule::SCHEDULE_ABLE)
        );

        $serviceSchedule = new Service_Data_Schedule();
        $scheduleInfos = $serviceSchedule->getListByConds($conds);

        $schedules      = array();
        $teacherUids    = array();
        $groupIds       = array();      
        if (!empty($scheduleInfos)) {
            foreach ($scheduleInfos as $item)  {
                if ($item['room_id'] <= 0) {
                    continue;
                }
                if (!isset($schedules[$item['room_id']])) {
                    $schedules[$item['room_id']] = array();
                }
                $schedules[$item['room_id']][] = array(
                    'sts'           => $item['start_time'],
                    'ets'           => $item['end_time'],
                    "teacher_uid"   => $item['teacher_uid'],
                    "group_id"      => $item['group_id'],
                );
            }
            $teacherUids = Zy_Helper_Utils::arrayInt($scheduleInfos, "teacher_uid");
            $groupIds = Zy_Helper_Utils::arrayInt($scheduleInfos, "group_id");
        }

        $userInfos = array();
        if (!empty($teacherUids)) {
            $serviceUser = new Service_Data_Profile();
            $userInfos = $serviceUser->getUserInfoByUids($teacherUids);
            $userInfos = array_column($userInfos, null, "uid");
        }

        $groupInfos = array();
        if (!empty($groupIds)) {
            $serviceGroup = new Service_Data_Group();
            $groupInfos = $serviceGroup->getListByConds(array(sprintf("id in (%s)", implode(",", $groupIds))));
            $groupInfos = array_column($groupInfos, null, "id");
        }

        $lists = $this->format($dateTime, $roomInfos, $schedules, $userInfos, $groupInfos);    
        if ($isExport) {
            $data = $this->formatExcel($lists);
            Zy_Helper_Utils::exportExcelSimple("arearoomdetail", $data['title'], $data['lists']);
        }
        return array(
            'lists' => $lists,
            'total' => count($lists),
        );
    }

    public function format($dateTime , $roomInfos, $schedules, $userInfos, $groupInfos) {
        $today = strtotime(date("Ymd 00:00:00", $dateTime));
        
        $column = array("name" => "");
        $timelen = array();

        for($i = 70; $i <=210; $i+=5 ) {
            $k = "T$i";
            $column[$k] = "-";
            $timelen[$k] = array(
                "sts" => $today + (intval($i / 10) * 3600) + ($i % 10 == 5 ? 1800 : 0)
            );
            $timelen[$k]['ets'] = $timelen[$k]['sts'] + 1800;
        }
        $output = array();

        foreach ($roomInfos as $roomId => $room) {
            $tmp = $column;
            $tmp['name'] = $room['name'];

            if (isset($schedules[$roomId])) {
                foreach ($schedules[$roomId] as $k1 => $item) {
                    $showMsg = sprintf("未知(可能相关必要信息被删导致)");
                    if (!empty($userInfos[$item['teacher_uid']]['nickname'])
                        && !empty($groupInfos[$item['group_id']]['name'])) {
                        $showMsg = sprintf("%s(%s)", $userInfos[$item['teacher_uid']]['nickname'], $groupInfos[$item['group_id']]['name']);
                    }
                    foreach ($timelen as $k2 => $t) {
                        if ($t['sts'] > $item['sts'] && $t['sts'] < $item['ets']) {
                            $tmp[$k2] = $showMsg;
                        }
                        if ($t['ets'] > $item['sts'] && $t['ets'] < $item['ets']) {
                            $tmp[$k2] = $showMsg;
                        }
                        if ($t['sts'] < $item['sts'] && $t['ets'] > $item['ets']) {
                            $tmp[$k2] = $showMsg;
                        }
                        if ($t['sts'] == $item['sts'] || $t['ets'] == $item['ets']) {
                            $tmp[$k2] = $showMsg;
                        }
                    }
                }
            }
            $output[] = $tmp;
        }

        return $output;
    }


    private function formatExcel($lists) {
        $result = array(
            'title' => array('房间', '07:00', '07:30', '08:00', '08:30', '09:00','09:30','10:00','10:30','11:00','11:30','12:00','12:30','13:00','13:30','14:00','14:30','15:00','15:30','16:00','16:30','17:00','17:30','18:00','18:30','19:00','19:30','20:00','20:30','21:00'),
            'lists' => array(),
        );
        
        foreach ($lists as $item) {
            $tmp = array(
                $item['name'],
                $item["T70"],
                $item["T75"],
                $item["T80"],
                $item["T85"],
                $item["T90"],
                $item["T95"],
                $item["T100"],
                $item["T105"],
                $item["T110"],
                $item["T115"],
                $item["T120"],
                $item["T125"],
                $item["T130"],
                $item["T135"],
                $item["T140"],
                $item["T145"],
                $item["T150"],
                $item["T155"],
                $item["T160"],
                $item["T165"],
                $item["T170"],
                $item["T175"],
                $item["T180"],
                $item["T185"],
                $item["T190"],
                $item["T195"],
                $item["T200"],
                $item["T205"],
                $item["T210"],
            );
            $result['lists'][] = $tmp;
        }
        return $result;

    }
}