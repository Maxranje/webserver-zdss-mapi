<?php

class Service_Page_Napi_Calendar_Platform extends Zy_Core_Service{

    public function execute (){
        if (!$this->checkAdmin() && !$this->checkTeacherPages()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $ets = empty($this->request['end_date']) ? "" : trim($this->request['end_date']);
        $sts = empty($this->request['start_date']) ? "" : trim($this->request['start_date']);
        $id  = empty($this->request['selected_id']) ? 0 : intval($this->request['selected_id']);
        $type= empty($this->request['type']) ? "" : trim($this->request['type']);

        if (empty($sts) || empty($ets)) {
            throw new Zy_Core_Exception(405, "操作失败, 请求参数不正确");
        }
        $sts = strtotime($sts);
        $ets = strtotime($ets);
        if ($sts < 1438185600 || $ets > 2700489600) {
            throw new Zy_Core_Exception(405, "操作失败, 时间范围不正确");
        }
        // 参数问题
        $sts += 86400;
        $ets += 86399;

        if (!in_array($type, array("group", "student", "teacher"))) {
            throw new Zy_Core_Exception(405, "操作失败, 类目不正确");
        }

        if ($id <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 选项不正确");
        }

        $lists = array();
        if ($type == "student") {
            $lists = $this->getStudentList($id, $sts, $ets);
        } else if ($type == "group") {
            $lists = $this->getGroupList($id, $sts, $ets);
        } else {
            $lists = $this->getTeacherList($id, $sts, $ets);
        }

        if (empty($lists)) {
            return array();
        }
        return array("lists" => $lists);
    }

    // 学员的
    private function getStudentList ($uid, $sts, $ets) {
        $serviceData = new Service_Data_Curriculum();
        $conds = array(
            sprintf("start_time >= %d", $sts),
            sprintf("end_time <= %d", $ets),
            sprintf("student_uid = %d", intval($uid))
        );

        $lists = $serviceData->getListByConds($conds);
        if (empty($lists)) {
            return array();
        }
                
        $teacherUids    = Zy_Helper_Utils::arrayInt($lists, "teacher_uid");
        $subjectIds     = Zy_Helper_Utils::arrayInt($lists, "subject_id");
        $areaIds        = Zy_Helper_Utils::arrayInt($lists, "area_id");
        $roomIds        = Zy_Helper_Utils::arrayInt($lists, "room_id");

        $serviceSubject = new Service_Data_Subject();
        $subjectInfo = $serviceSubject->getListByConds(array('id in ('.implode(',', $subjectIds).')'));
        $subjectInfo = array_column($subjectInfo, null, 'id');

        $subjectParentIds = Zy_Helper_Utils::arrayInt($subjectInfo, "parent_id");
        $subjectParentInfos = $serviceSubject->getSubjectByIds($subjectParentIds);
        $subjectParentInfos = array_column($subjectParentInfos, null, 'id');

        $serviceUser = new Service_Data_Profile();
        $userInfos = $serviceUser->getListByConds(array('uid in ('.implode(',', $teacherUids).')'));
        $userInfos = array_column($userInfos, null, 'uid');

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
            if (empty($userInfos[$item['teacher_uid']]['nickname'])) {
                continue;
            }
            if (empty($subjectInfo[$item['subject_id']]['name'])) {
                continue;
            }
            if (empty($subjectInfo[$item['subject_id']]['parent_id'])) {
                continue;
            }
            $subjectParentId = $subjectInfo[$item['subject_id']]['parent_id'];
            if (empty($subjectParentInfos[$subjectParentId]['name'])) {
                continue;
            }
            // 教师信息
            $teacherName = $userInfos[$item['teacher_uid']]['nickname'];
            // 科目信息
            $subjectName = sprintf("%s/%s", $subjectParentInfos[$subjectParentId]['name'], $subjectInfo[$item['subject_id']]['name']);
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

            $result[] = array(
                "start" => date("Y-m-d H:i:s",$item['start_time']),
                "end"   => date("Y-m-d H:i:s",$item['end_time']),
                "extendedProps" => array(
                    "teacher" => $teacherName,
                    "subject" => $subjectName,
                    "location" => $areaName,
                    "state" =>  $item["state"] == Service_Data_Schedule::SCHEDULE_ABLE ? 2 : 3,
                ),
            );
        }
        return $result;
    }

    private function getTeacherList ($uid, $sts, $ets) {
        $serviceData = new Service_Data_Schedule();
        $conds = array(
            sprintf("start_time >= %d", $sts),
            sprintf("end_time <= %d", $ets),
            sprintf("teacher_uid = %d", intval($uid))
        );

        $lists = $serviceData->getListByConds($conds);
        if (empty($lists)) {
            return array();
        }
                
        $groupIds       = Zy_Helper_Utils::arrayInt($lists, "group_id");
        $subjectIds     = Zy_Helper_Utils::arrayInt($lists, "subject_id");
        $areaIds        = Zy_Helper_Utils::arrayInt($lists, "area_id");
        $roomIds        = Zy_Helper_Utils::arrayInt($lists, "room_id");

        $serviceSubject = new Service_Data_Subject();
        $subjectInfo = $serviceSubject->getListByConds(array('id in ('.implode(',', $subjectIds).')'));
        $subjectInfo = array_column($subjectInfo, null, 'id');

        $subjectParentIds = Zy_Helper_Utils::arrayInt($subjectInfo, "parent_id");
        $subjectParentInfos = $serviceSubject->getSubjectByIds($subjectParentIds);
        $subjectParentInfos = array_column($subjectParentInfos, null, 'id');

        $serviceData = new Service_Data_Group();
        $groupInfos = $serviceData->getListByConds(array('id in ('.implode(',', $groupIds).')'));
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
            if (empty($groupInfos[$item['group_id']]['name'])) {
                continue;
            }
            if (empty($subjectInfo[$item['subject_id']]['name'])) {
                continue;
            }
            if (empty($subjectInfo[$item['subject_id']]['parent_id'])) {
                continue;
            }
            $subjectParentId = $subjectInfo[$item['subject_id']]['parent_id'];
            if (empty($subjectParentInfos[$subjectParentId]['name'])) {
                continue;
            }
            // 信息
            $groupName = $groupInfos[$item['group_id']]['name'];
            // 科目信息
            $subjectName = sprintf("%s/%s", $subjectParentInfos[$subjectParentId]['name'], $subjectInfo[$item['subject_id']]['name']);
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

            $result[] = array(
                "start" => date("Y-m-d H:i:s",$item['start_time']),
                "end"   => date("Y-m-d H:i:s",$item['end_time']),
                "extendedProps" => array(
                    "teacher" => $groupName,
                    "subject" => $subjectName,
                    "location" => $areaName,
                    "state" =>  $item["state"] == Service_Data_Schedule::SCHEDULE_ABLE ? 2 : 3,
                ),
            );
        }
        return $result;
    }

    private function getGroupList ($gid, $sts, $ets) {
        $serviceData = new Service_Data_Schedule();
        $conds = array(
            sprintf("start_time >= %d", $sts),
            sprintf("end_time <= %d", $ets),
            sprintf("group_id = %d", intval($gid))
        );

        $lists = $serviceData->getListByConds($conds);
        if (empty($lists)) {
            return array();
        }
                
        $teacherUids    = Zy_Helper_Utils::arrayInt($lists, "teacher_uid");
        $subjectIds     = Zy_Helper_Utils::arrayInt($lists, "subject_id");
        $areaIds        = Zy_Helper_Utils::arrayInt($lists, "area_id");
        $roomIds        = Zy_Helper_Utils::arrayInt($lists, "room_id");

        $serviceSubject = new Service_Data_Subject();
        $subjectInfo = $serviceSubject->getListByConds(array('id in ('.implode(',', $subjectIds).')'));
        $subjectInfo = array_column($subjectInfo, null, 'id');

        $subjectParentIds = Zy_Helper_Utils::arrayInt($subjectInfo, "parent_id");
        $subjectParentInfos = $serviceSubject->getSubjectByIds($subjectParentIds);
        $subjectParentInfos = array_column($subjectParentInfos, null, 'id');

        $serviceUser = new Service_Data_Profile();
        $userInfos = $serviceUser->getListByConds(array('uid in ('.implode(',', $teacherUids).')'));
        $userInfos = array_column($userInfos, null, 'uid');

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
            if (empty($userInfos[$item['teacher_uid']]['nickname'])) {
                continue;
            }
            if (empty($subjectInfo[$item['subject_id']]['name'])) {
                continue;
            }
            if (empty($subjectInfo[$item['subject_id']]['parent_id'])) {
                continue;
            }
            $subjectParentId = $subjectInfo[$item['subject_id']]['parent_id'];
            if (empty($subjectParentInfos[$subjectParentId]['name'])) {
                continue;
            }
            // 教师信息
            $teacherName = $userInfos[$item['teacher_uid']]['nickname'];
            // 科目信息
            $subjectName = sprintf("%s/%s", $subjectParentInfos[$subjectParentId]['name'], $subjectInfo[$item['subject_id']]['name']);
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

            $result[] = array(
                "start" => date("Y-m-d H:i:s",$item['start_time']),
                "end"   => date("Y-m-d H:i:s",$item['end_time']),
                "extendedProps" => array(
                    "teacher" => $teacherName,
                    "subject" => $subjectName,
                    "location" => $areaName,
                    "state" =>  $item["state"] == Service_Data_Schedule::SCHEDULE_ABLE ? 2 : 3,
                ),
            );
        }
        return $result;
    }    
}