<?php

class Service_Page_Schedule_Calendar_Startsearch extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $groupId        = empty($this->request['group_id']) ? 0 : intval($this->request['group_id']);
        $teacherUid     = empty($this->request['teacher_uid']) ? 0 : intval($this->request['teacher_uid']);
        
        if ($groupId <= 0 && $teacherUid <= 0) {
            return array();
        }

        $type = Service_Data_Profile::USER_TYPE_STUDENT;
        if ($groupId == 0 && $teacherUid > 0) {
            $type = Service_Data_Profile::USER_TYPE_TEACHER;
        }

        $columnIds = array();
        if ($type == Service_Data_Profile::USER_TYPE_TEACHER) {
            $serviceColumn = new Service_Data_Column();
            $columnInfos = $serviceColumn->getColumnByTId($teacherUid);
            $columnIds = array_column($columnInfos, "id");
        }

        $conds = array();
        if ($groupId > 0) {
            $conds['group_id'] = $groupId;
        } else if (!empty($columnIds)) {
            $conds[] = sprintf('column_id in (%s)', implode(",", $columnIds));
        }

        $sts = time() - (6 * 30 * 86400);
        $ets = time() + (6 * 30 * 86400);

        $conds[] = sprintf("start_time >= %d", $sts);
        $conds[] = sprintf("end_time <= %d", $ets);

        $arrAppends[] = 'order by start_time';

        $serviceSchedule = new Service_Data_Schedule();
        $lists = $serviceSchedule->getListByConds($conds, false, null, $arrAppends);
        
        // 是否有锁的日程
        $lock = array();
        if ($type == Service_Data_Profile::USER_TYPE_TEACHER) {
            $serviceLock = new Service_Data_Lock();
            $lock = $serviceLock->getListByUid($teacherUid, $sts, $ets);
        }

        $lists = $this->formatSelect($lists, $lock);

        return array('schedules' => $lists);
    }

    private function formatSelect ($lists, $lock) {
        if (empty($lists) && empty($lock)) {
            return array();
        }

        $columnIds = Zy_Helper_Utils::arrayInt($lists, "column_id");
        $groupIds = Zy_Helper_Utils::arrayInt($lists, "group_id");
        $areaIds = Zy_Helper_Utils::arrayInt($lists, "area_id");
        $roomIds = Zy_Helper_Utils::arrayInt($lists, "room_id");
        $uids = Zy_Helper_Utils::arrayInt($lists, "teacher_uid");
        $lu = Zy_Helper_Utils::arrayInt($lock, "uid");

        $uids = array_unique(array_merge($uids, $lu));

        // 获取绑定
        $serviceColumn = new Service_Data_Column();
        $columnInfos = $serviceColumn->getListByConds(array('id in ('.implode(',', $columnIds).')'));
        $columnInfos = array_column($columnInfos, null, 'id');
        $subjectIds = Zy_Helper_Utils::arrayInt($columnInfos, "subject_id");

        // 获取用户信息
        $serviceUser = new Service_Data_Profile();
        $userInfos = $serviceUser->getListByConds(array('uid in ('.implode(',', $uids).')'));
        $userInfos = array_column($userInfos, null, 'uid');

        // 获取班级
        $serviceGroup = new Service_Data_Group();
        $groupInfos = $serviceGroup->getListByConds(array('id in ('.implode(",", $groupIds).')'));
        $groupInfos = array_column($groupInfos, null, 'id');

        // 获取科目
        $serviceSubject = new Service_Data_Subject();
        $subjectInfos = $serviceSubject->getSubjectByIds($subjectIds);
        $subjectInfos = array_column($subjectInfos, null, 'id');

        $subjectParentIds = Zy_Helper_Utils::arrayInt($subjectInfos, "parent_id");
        $subjectParentInfos = $serviceSubject->getSubjectByIds($subjectParentIds);
        $subjectParentInfos = array_column($subjectParentInfos, null, 'id');

        // 校区和房间
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
        foreach ($lists as $item) {
            if (empty($userInfos[$item['teacher_uid']]['nickname'])) {
                continue;
            }
            if (empty($groupInfos[$item['group_id']]['name'])) {
                continue;
            }
            if (empty($columnInfos[$item['column_id']]['subject_id'])) {
                continue;
            }
            if (empty($subjectInfos[$columnInfos[$item['column_id']]['subject_id']]['name'])) {
                continue;
            }
            if (empty($subjectInfos[$columnInfos[$item['column_id']]['subject_id']]['parent_id'])) {
                continue;
            }
            $subjectParentId = $subjectInfos[$columnInfos[$item['column_id']]['subject_id']]['parent_id'];
            if (empty($subjectParentInfos[$subjectParentId]['name'])) {
                continue;
            }

            $teacherNickname = $userInfos[$item['teacher_uid']]['nickname'];
            $groupName = $groupInfos[$item['group_id']]['name'];
            $subjectName = sprintf("%s/%s", $subjectParentInfos[$subjectParentId]['name'], $subjectInfos[$columnInfos[$item['column_id']]['subject_id']]['name']);
            
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
                "className" => $item['state'] == Service_Data_Schedule::SCHEDULE_DONE ? "bg-pink-800" : "bg-green-700",
            );

            if ($this->request['group_id'] > 0) {
                $tm['content'] = date('H:i', $item['start_time']) . "-".date('H:i', $end_time). " " .$subjectName . "-" . $teacherNickname;
            } else {
                $tm['content'] = date('H:i', $item['start_time']) . "-".date('H:i', $end_time). " " .$teacherNickname . "-" . $groupName;
            }

            $result[] = $tm;
        }
        if  (empty($lock)) {
            return $result;
        }

        // 教师锁定时间
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
            $result[] = $tm;
        }

        return $result;
    }
}