<?php

// 排课中查询功能
class Service_Page_Schedule_Pkcalendar extends Zy_Core_Service{

    public $serviceSchedule;

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $groupId    = empty($this->request['group_id']) ? 0 : intval($this->request['group_id']);
        $teacherId  = empty($this->request['teacher_id']) ? 0 : intval($this->request['teacher_id']);
        
        if (empty($groupId) && empty($teacherId)) {
            return array();
        }

        $sts = time() - (6 * 30 * 86400);
        $ets = time() + (6 * 30 * 86400);

        $output = array(
            "type" => "calendar",
            "largeMode" => true,
            "value" => time(),
            "schedules" => array(),
        );   

        $type = Service_Data_User_Profile::USER_TYPE_STUDENT;
        if ($groupId == 0 && $teacherId > 0) {
            $type = Service_Data_User_Profile::USER_TYPE_TEACHER;
        }

        $columnIds = array();
        if ($type == Service_Data_User_Profile::USER_TYPE_TEACHER) {
            $serviceColumn = new Service_Data_Column();
            $columnInfos = $serviceColumn->getColumnByTId($teacherId);
            $columnIds = array_column($columnInfos, "id");
        }

        $conds = array();
        if ($groupId > 0) {
            $conds['group_id'] = $groupId;
        } else if (!empty($columnIds)) {
            $conds[] = sprintf('column_id in (%s)', implode(",", $columnIds));
        }

        $conds[] = "start_time >= ".$sts;
        $conds[] = "end_time <= ".$ets;

        $arrAppends[] = 'order by start_time';

        $this->serviceSchedule = new Service_Data_Schedule();
        $lists = $this->serviceSchedule->getListByConds($conds, false, null, $arrAppends);
        
        // 是否有锁的日程
        $lock = array();
        if ($type == Service_Data_User_Profile::USER_TYPE_TEACHER) {
            $serviceLock = new Service_Data_Lock();
            $lock = $serviceLock->getLockListByUid($teacherId, $sts, $ets);
        }

        $lists = $this->formatSelect($lists, $lock);

        $output['schedules'] = $lists;
        return $output;
    }

    private function formatSelect ($lists, $lock) {
        if (empty($lists) && empty($lock)) {
            return array();
        }

        $resultList = array();

        // 初始化参数
        $columnIds      = array();
        $groupIds       = array();
        $areaIds        = array();
        $roomIds        = array();
        $uids           = array();
        foreach ($lists as $item) {
            $columnIds[intval($item['column_id'])] = intval($item['column_id']);
            $groupIds[intval($item['group_id'])] = intval($item['group_id']);
            $uids[intval($item['teacher_id'])] = intval($item['teacher_id']);
            
            // 获取校区id
            if (!empty($item['area_id'])) {
                $areaIds[intval($item['area_id'])] = intval($item['area_id']);
            }
            if (!empty($item['room_id'])) {
                $roomIds[intval($item['room_id'])] = intval($item['room_id']);
            }
        }
        foreach ($lock as $item) {
            $uids[intval($item['uid'])] = intval($item['uid']);
        }
        $columnIds = array_values($columnIds);
        $groupIds = array_values($groupIds);
        $areaIds = array_values($areaIds);
        $roomIds = array_values($roomIds);
        $uids = array_values($uids);

        // 获取教师名字
        $serviceColumn = new Service_Data_Column();
        $columnInfos = $serviceColumn->getListByConds(array('id in ('.implode(',', $columnIds).')'));
        $subject_ids = array_column($columnInfos, 'subject_id');
        $columnInfos = array_column($columnInfos, null, 'id');

        $serviceUser = new Service_Data_User_Profile();
        $userInfos = $serviceUser->getListByConds(array('uid in ('.implode(',', $uids).')'));
        $userInfos = array_column($userInfos, null, 'uid');

        $serviceGroup = new Service_Data_Group();
        $groupInfos = $serviceGroup->getListByConds(array('id in ('.implode(",", $groupIds).')'));
        $groupInfos = array_column($groupInfos, null, 'id');

        $serviceSubject = new Service_Data_Subject();
        $subjectInfos = $serviceSubject->getListByConds(array('id in ('.implode(",", $subject_ids).')'));
        $subjectInfos = array_column($subjectInfos, null, 'id');

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
        
        foreach ($lists as $item) {
            $tid = $item['teacher_id'];
            if (empty($userInfos[$tid]['nickname'])) {
                continue;
            }
            $tname = $userInfos[$tid]['nickname'];

            if ($item['state'] == 3) {
                $resultList[] = array(
                    'startTime' => date('Y-m-d H:i:s', $item['start_time']),
                    'endTime' => date('Y-m-d H:i:s', $item['end_time']),
                    "className" => "bg-gray-400",
                    'content' => date('H:i', $item['start_time']) . "-".date('H:i', $item['end_time']). " " .$tname . "锁定",
                );
                continue;
            }

            if (empty($groupInfos[$item['group_id']]['name'])) {
                continue;
            }
            $gname = $groupInfos[$item['group_id']]['name'];

            if (empty($columnInfos[$item['column_id']]['subject_id'])) {
                continue;
            }
            $sid = $columnInfos[$item['column_id']]['subject_id'];
            $sname = $subjectInfos[$sid]['name'];
            
            // 校区信息
            $areaName = "";
            if (!empty($item['area_id']) && !empty($areaInfos[$item['area_id']]['name'])) {
                $areaName = $areaInfos[$item['area_id']]['name'];
                if (!empty($item['room_id']) && !empty($roomInfos[$item['room_id']]['name'])) {
                    $areaName = sprintf("%s_%s", $areaName, $roomInfos[$item['room_id']]['name']);
                } else {
                    $areaName = sprintf("%s_%s", $areaName, "无教室");
                }
                // 学生线上课, 教师线下
                $ext = empty($item['ext']) ? array() : json_decode($item['ext'], true);
                if (isset($ext['is_online']) && $ext['is_online'] == 1) {
                    $areaName .= "(线上)";
                }
            }
            
            $end_time = $item['end_time'];
            if (date('H:s', $end_time) == "00:00") {
                $item['end_time'] -= 1;
            }

            $tm = array(
                'startTime' => date('Y-m-d H:i:s', $item['start_time']),
                'endTime' => date('Y-m-d H:i:s', $item['end_time']),
                "className" => $item['state'] == 1 ? "bg-pink-800" : "bg-green-700",
            );

            if ($this->request['group_id'] > 0) {
                $tm['content'] = date('H:i', $item['start_time']) . "-".date('H:i', $end_time). " " .$sname . "-" . $tname;
            } else {
                $tm['content'] = date('H:i', $item['start_time']) . "-".date('H:i', $end_time). " " .$sname . "-" . $gname;
            }

            $resultList[] = $tm;
        }

        if (!empty($lock)) {
            foreach ($lock as $item) {
                if (empty($userInfos[$item['uid']]['nickname'])) {
                    continue;
                }
                $end_time = $item['end_time'];
                if (date('H:s', $end_time) == "00:00") {
                    $item['end_time'] -= 1;
                }
                $tm = array(
                    'startTime' => date('Y-m-d H:i:s', $item['start_time']),
                    'endTime' => date('Y-m-d H:i:s', $item['end_time']),
                    "className" =>"bg-gray-700",
                    "content" => date('H:i', $item['start_time']) . "-".date('H:i', $end_time). " 教师锁定时间",
                );
                $resultList[] = $tm;
            }
        }

        return $resultList;
    }
}