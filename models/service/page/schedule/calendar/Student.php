<?php

// 后台学生管理学生端查询
class Service_Page_Schedule_Calendar_Student extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $studentUid  = empty($this->request['student_uid']) ? 0 : intval($this->request['student_uid']);

        $sts = time() - (3 * 30 * 86400);
        $ets = time() + (3 * 30 * 86400);

        if ($studentUid <= 0) {
            return array();
        }

        $conds = array(
            'student_uid' => $studentUid,
        );

        $conds[] = sprintf("start_time >= %d", $sts);
        $conds[] = sprintf("end_time <= %d", $ets);

        $serviceCurriculum = new Service_Data_Curriculum();
        $lists = $serviceCurriculum->getListByConds($conds, false, null, null);

        return array('schedules' => $this->formatSelect($lists));
    }

    private function formatSelect ($lists) {
        if (empty($lists)) {
            return array();
        }

        $columnIds = Zy_Helper_Utils::arrayInt($lists, "column_id");
        $areaIds = Zy_Helper_Utils::arrayInt($lists, "area_id");
        $roomIds = Zy_Helper_Utils::arrayInt($lists, "room_id");
        $teacherUids = Zy_Helper_Utils::arrayInt($lists, "teacher_uid");

        // 获取绑定
        $serviceColumn = new Service_Data_Column();
        $columnInfos = $serviceColumn->getListByConds(array('id in ('.implode(',', $columnIds).')'));
        $columnInfos = array_column($columnInfos, null, 'id');
        $subjectIds = Zy_Helper_Utils::arrayInt($columnInfos, "subject_id");

        // 获取用户信息
        $serviceUser = new Service_Data_Profile();
        $userInfos = $serviceUser->getListByConds(array('uid in ('.implode(',', $teacherUids).')'));
        $userInfos = array_column($userInfos, null, 'uid');

        // 获取科目
        $serviceSubject = new Service_Data_Subject();
        $subjectInfos = $serviceSubject->getListByConds(array('id in ('.implode(",", $subjectIds).')'));
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
                "content" => date('H:i', $item['start_time']) . "-".date('H:i', $end_time). " " .$subjectName . "-" . $teacherNickname,
            );

            $result[] = $tm;
        }
        return $result;
    }
}