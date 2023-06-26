<?php
// 学生/教师端显示
class Service_Page_Schedule_Fscalendar extends Zy_Core_Service{

    public $serviceSchedule;
    public function execute () {
        if (!$this->checkStudent() && !$this->checkTeacher()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $uid = $this->adption['userid'];
        $type = $this->adption['type'];

        $ets = strtotime(date('Y-m-d',  strtotime("+60 day")));
        $sts = strtotime(date('Y-m-d',  strtotime("-90 day")));

        $columnIds = array();
        if ($type == Service_Data_User_Profile::USER_TYPE_TEACHER) {
            $serviceColumn = new Service_Data_Column();
            $columnInfos = $serviceColumn->getColumnByTId($uid);
            if (empty($columnInfos)) {
                return array();
            }

            foreach ($columnInfos as $key => $info) {
                $columnIds[] = intval($info['id']);
            }
        }

        $groupIds = array();
        if ($type == Service_Data_User_Profile::USER_TYPE_STUDENT) {
            $serviceGroup = new Service_Data_User_Group();
            $groupMapInfo = $serviceGroup->getGroupMapBySid($uid);
            if (empty($groupMapInfo)) {
                return array();
            }

            foreach ($groupMapInfo as $info) {
                $groupIds[] = intval($info['group_id']);
            }
        }

        $this->serviceSchedule = new Service_Data_Schedule();

        $conds = array();
        if (!empty($groupIds)) {
            $conds[] = sprintf('group_id in (%s)', implode(",", $groupIds));
        } 
        if (!empty($columnIds)) {
            $conds[] = sprintf('column_id in (%s)', implode(",", $columnIds));
        }
        $conds[] = "start_time >= ".$sts;
        $conds[] = "end_time <= ".$ets;

        // 基础
        $lists = $this->serviceSchedule->getListByConds($conds);

        // 是否有锁的日程
        $lock = array();
        if ($type == Service_Data_User_Profile::USER_TYPE_TEACHER) {
            $serviceLock = new Service_Data_Lock();
            $lock = $serviceLock->getLockListByUid($uid, $sts, $ets);
        }
        
        echo json_encode($this->formatBase($lists, $lock, $type));
        exit;
    }

    private function formatBase ($lists, $lock, $type) {
        if (empty($lists) && empty($lock)) {
            return array();
        }

        $teacherIds     = array();
        $columnIds      = array();
        $groupIds       = array();
        $areaIds        = array();
        $roomIds        = array();
        foreach ($lists as $item) {
            $columnIds[intval($item['column_id'])] = intval($item['column_id']);
            $groupIds[intval($item['group_id'])] = intval($item['group_id']);
            $teacherIds[intval($item['teacher_id'])] = intval($item['teacher_id']);
            
            // 获取校区id
            if (!empty($item['area_id'])) {
                $areaIds[intval($item['area_id'])] = intval($item['area_id']);
            }
            if (!empty($item['room_id'])) {
                $roomIds[intval($item['room_id'])] = intval($item['room_id']);
            }
        }
        foreach ($lock as $item) {
            $teacherIds[intval($item['uid'])] = intval($item['uid']);
        }
        
        $teacherIds = array_values($teacherIds);
        $columnIds = array_values($columnIds);
        $groupIds = array_values($groupIds);
        $areaIds = array_values($areaIds);
        $roomIds = array_values($roomIds);

        // 获取教师名字
        $serviceColumn = new Service_Data_Column();
        $columnInfos = $serviceColumn->getListByConds(array('id in ('.implode(',', $columnIds).')'));
        $subject_ids = array_column($columnInfos, 'subject_id');
        $columnInfos = array_column($columnInfos, null, 'id');

        $serviceSubject = new Service_Data_Subject();
        $subjectInfo = $serviceSubject->getListByConds(array('id in ('.implode(',', $subject_ids).')'));
        $subjectInfo = array_column($subjectInfo, null, 'id');

        $serviceUser = new Service_Data_User_Profile();
        $userInfos = $serviceUser->getListByConds(array('uid in ('.implode(',', $teacherIds).')'));
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
            $tmp = array();
            $tmp['start'] = date("Y-m-d H:i:s",$item['start_time']);
            $tmp['end'] = date("Y-m-d H:i:s",$item['end_time']);

            if (empty($columnInfos[$item['column_id']]['subject_id'])) {
                continue;
            }
            
            $tid = $item['teacher_id'];
            $sid = $columnInfos[$item['column_id']]['subject_id'];
            if (empty($userInfos[$tid]['nickname'])
                || empty($subjectInfo[$sid]['name'])
                || empty($groupInfos[$item['group_id']]['name'])) {
                continue;
            }
            $teacherName = $userInfos[$tid]['nickname'];
            $subjectName = $subjectInfo[$sid]['name'];
            $groupName = $groupInfos[$item['group_id']]['name'];

            $ext = empty($item['ext']) ? array() : json_decode($item['ext'], true);

            // 校区信息
            $areaName = "";
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


            if ($type == Service_Data_User_Profile::USER_TYPE_TEACHER) {
		        $duration = sprintf("%.2f", ($item['end_time'] - $item['start_time']) / 3600) . "小时";
                $tmp['title'] = sprintf("%s %s %s %s", $duration, $groupName, $subjectName, $areaName);    
		        if ($item['state'] == 0) {
                    $tmp['title'] .= "(已结算)";
                }
            } else {
                if (isset($ext['is_online']) && $ext['is_online'] == 1) {
                    $areaName = "线上";
                }
                $tmp['title'] = sprintf("%s %s %s", $subjectName, $teacherName, $areaName);
            }

            $result[] = $tmp;            
        }

        if (!empty($lock)) {
            foreach ($lock as $item) {
                if (empty($userInfos[$item['uid']]['nickname'])) {
                    continue;
                }
                $tmp = array();
                $tmp['start'] = date("Y-m-d H:i:s",$item['start_time']);
                $tmp['end'] = date("Y-m-d H:i:s",$item['end_time']);
                $tmp['title'] = $userInfos[$item['uid']]['nickname'] . "锁定时间";
                $result[] = $tmp;
            }
        }

        return $result;
    }
}
