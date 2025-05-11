<?php

class Service_Page_Log_Lists extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $pn         = empty($this->request['page']) ? 0 : intval($this->request['page']);
        $rn         = empty($this->request['perPage']) ? 20 : intval($this->request['perPage']);
        $point      = empty($this->request['point']) ? 0 : intval($this->request['point']);
        $uid        = empty($this->request['uid']) ? 0 : intval($this->request['uid']);
        $workId     = empty($this->request['uid']) ? 0 : intval($this->request['work_id']);
        $dataRange  = empty($this->request['daterangee']) ? array() : explode(",", $this->request['daterangee']);

        $pn = $pn - 1 <= 0 ? 0 : ($pn-1) * $rn;

        if ($point <= 0) {
            return array();
        }

        $conds = array(
            "point" => $point,
        );
        if ($uid > 0) {
            $conds["uid"] = $uid;
        }
        if ($workId > 0) {
            $conds["work_id"] = $workId;
        }
        if (!empty($dataRange)) {
            $conds[] = sprintf("create_time >= %d", $dataRange[0]);
            $conds[] = sprintf("create_time <= %d", ($dataRange[1] + 1));
        }

        $arrAppends[] = 'order by id desc';
        $arrAppends[] = "limit {$pn} , {$rn}";
        
        $serviceData = new Service_Data_Operationlog();
        $lists = $serviceData->getListByConds($conds, array(), null, $arrAppends);
        if ($point == Service_Data_Operationlog::SCHEDULE_EDIT) {
            $lists = $this->formatScheduleEdit($lists);
        } else {
            $lists = array();
        }
        if (empty($lists)) {
            return array();
        }
        $total = $serviceData->getTotalByConds($conds);

        return array(
            'rows' => $lists,
            'total' => $total,
        );
    }

    private function formatScheduleEdit($lists) {
        if (empty($lists)) {
            return array();
        }
        
        $uids = Zy_Helper_Utils::arrayInt($lists, 'uid');
        $teacherUids = $areaIds = $roomIds = $subjectIds = array();

        foreach ($lists as $k => &$v) {
            $current = json_decode($v["current_data"], true);
            $original = json_decode($v["original_data"], true);

            $teacherUids[] = intval($current['teacher_uid']);
            $areaIds[] = intval($current['area_id']);
            $roomIds[] = intval($current['room_id']);
            $subjectIds[] = intval($current['subject_id']);

            $teacherUids[] = intval($original['teacher_uid']);
            $areaIds[] = intval($original['area_id']);
            $roomIds[] = intval($original['room_id']);
            $subjectIds[] = intval($original['subject_id']);

            $v['current_data'] = $current;
            $v['original_data'] = $original;
        }

        $uids = array_unique(array_merge($uids, $teacherUids));
        $areaIds = array_unique($areaIds);
        $roomIds = array_unique($roomIds);
        $subjectIds = array_unique($subjectIds);

        $serviceData = new Service_Data_Profile();
        $userInfos = $serviceData->getUserInfoByUids($uids);
        $userInfos = array_column($userInfos, null, "uid");

        $areaInfos = array();
        if (!empty($areaIds)) {
            $serviceData = new Service_Data_Area();
            $areaInfos = $serviceData->getAreaByIds($areaIds, true);
            foreach ($areaInfos as &$v) {
                $v["rooms"] = array_column($v["rooms"], null, "id");
            }
            $areaInfos = array_column($areaInfos, null, "id");
        }

        $subjectInfos = array();
        if (!empty($subjectIds)) {
            $serviceData = new Service_Data_Subject();
            $subjectInfos = $serviceData->getSubjectByIds($subjectIds, true);
            $subjectInfos = array_column($subjectInfos, null, "id");
        }

        $result = array();
        foreach ($lists as $v) {
            $tmp = array(
                "create_time" => date("Y-m-d H:i:s", $v['create_time']),
                "uid" => $v["uid"],
                "work_id" => $v["work_id"],
                "nickname" => empty($userInfos[$v['uid']]['nickname']) ? "已删除" : $userInfos[$v['uid']]['nickname'],
            );
            $otUid = $v["original_data"]['teacher_uid'];
            $ctUid = $v["current_data"]['teacher_uid'];
            $otSid = $v["original_data"]['subject_id'];
            $ctSid = $v["current_data"]['subject_id'];
            $otAid = empty($v["original_data"]['area_id']) ? 0 : $v["original_data"]['area_id'];
            $ctAid = empty($v["current_data"]['area_id']) ? 0 : $v["current_data"]['area_id'];
            $otRid = empty($v["original_data"]['room_id']) ? 0 : $v["original_data"]['room_id'];
            $ctRid = empty($v["current_data"]['room_id']) ? 0 : $v["current_data"]['room_id'];

            $otName = empty($userInfos[$otUid]['nickname']) ? "-" : $userInfos[$otUid]['nickname'];
            $ctName = empty($userInfos[$ctUid]['nickname']) ? "-" : $userInfos[$ctUid]['nickname'];
            $osName = empty($subjectInfos[$otSid]['name']) ? "-" : $subjectInfos[$otSid]['name'];
            $csName = empty($subjectInfos[$ctSid]['name']) ? "-" : $subjectInfos[$ctSid]['name'];
            $otAname = empty($areaInfos[$otAid]['name']) ? "-" : $areaInfos[$otAid]['name'];
            $ctAname = empty($areaInfos[$ctAid]['name']) ? "-" : $areaInfos[$ctAid]['name'];

            $otRname = empty($areaInfos[$otAid]['rooms'][$otRid]['name']) ? "-" : $areaInfos[$otAid]['rooms'][$otRid]['name'];
            $ctRname = empty($areaInfos[$ctAid]['rooms'][$ctRid]['name']) ? "-" : $areaInfos[$ctAid]['rooms'][$ctRid]['name'];

            $tmp["original"] = array(
                "教师" => sprintf("%s(id: %s)", $otName, $otUid),
                "校区" => sprintf("%s(id: %s)", $otAname, $otUid),
                "课程" => sprintf("%s(id: %s)", $osName, $otSid),
                "教室" => sprintf("%s(id: %s)", $otRname, $otRid),
                '排课时间' =>sprintf("%s ~ %s", date("Y-m-d H:i", $v["original_data"]['start_time']),  date("H:i", $v["original_data"]['end_time'])),
            );
            $tmp["current"] = array(
                "教师" => sprintf("%s(id: %s)", $ctName, $ctUid),
                "校区" => sprintf("%s(id: %s)", $ctAname, $ctUid),
                "课程" => sprintf("%s(id: %s)", $csName, $ctSid),
                "教室" => sprintf("%s(id: %s)", $ctRname, $ctRid),
                '排课时间' =>sprintf("%s ~ %s", date("Y-m-d H:i", $v["current_data"]['start_time']),  date("H:i", $v["current_data"]['end_time'])),
            );

            $result[] = $tmp;
        }
        return $result;
    }
}   