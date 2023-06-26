<?php

class Service_Page_Area_Details extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限");
        }

        $areaId = empty($this->request['area_id']) ? 0 : intval($this->request['area_id']);
        $dateTime = empty($this->request['datetime']) ? 0 : intval($this->request['datetime']);

        if ($areaId <= 0 && $dateTime <= 0 ) {
            return array();
        }

        if ($areaId <= 0 || $dateTime <= 0 ) {
            throw new Zy_Core_Exception(405, "校区或时间必须进行选择");
        }

        $serviceArea = new Service_Data_Area();
        $roomInfos = $serviceArea->getRoomListByAid($areaId);
        if (empty($roomInfos)) {
            throw new Zy_Core_Exception(405, "该校区下暂时没有教室");
        }
        $roomInfos = array_column($roomInfos, null, "id");

        $sts = $dateTime + (7 * 3600);
        $ets = $dateTime + (21 * 3600);

        $conds = array(
            "area_id = ". $areaId,
            "start_time >= " . $sts,
            "state=1", 
            "end_time <= " . $ets,
        );

        $serviceSchedule = new Service_Data_Schedule();
        $scheduleInfos = $serviceSchedule->getListByConds($conds);

        $schedules = array();
        $teacherIds = array();
        $groupIds = array();      
        if (!empty($scheduleInfos)) {
            foreach ($scheduleInfos as $item)  {
                if ($item['room_id'] <= 0) {
                    continue;
                }
                if (!isset($schedules[$item['room_id']])) {
                    $schedules[$item['room_id']] = array();
                }
                $schedules[$item['room_id']][] = array(
                    'sts' => $item['start_time'],
                    'ets' => $item['end_time'],
                    "teacher_id" => $item['teacher_id'],
                    "group_id" => $item['group_id'],
                );
                $teacherIds[intval($item['teacher_id'])] = intval($item['teacher_id']);
                $groupIds[intval($item['group_id'])] = intval($item['group_id']);
            }
            $teacherIds = array_values($teacherIds);
            $groupIds = array_values($groupIds);
        }

        $userInfos = array();
        if (!empty($teacherIds)) {
            $serviceUser = new Service_Data_User_Profile();
            $userInfos = $serviceUser->getUserInfoByUids($teacherIds);
            $userInfos = array_column($userInfos, null, "uid");
        }

        $groupInfos = array();
        if (!empty($groupIds)) {
            $serviceGroup = new Service_Data_Group();
            $groupInfos = $serviceGroup->getListByConds(array(sprintf("id in (%s)", implode(",", $groupIds))));
            $groupInfos = array_column($groupInfos, null, "id");
        }

        $lists = $this->format($dateTime, $roomInfos, $schedules, $userInfos, $groupInfos);    
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
                    if (!empty($userInfos[$item['teacher_id']]['nickname'])
                        && !empty($groupInfos[$item['group_id']]['name'])) {
                        $showMsg = sprintf("%s(%s)", $userInfos[$item['teacher_id']]['nickname'], $groupInfos[$item['group_id']]['name']);
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
}