<?php
// 学生/教师端显示
class Service_Page_Schedule_Calendar_Client extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkStudent() && !$this->checkTeacher()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $sts = empty($this->request['start']) ? strtotime(date('Y-m-d',  strtotime("-45 day"))) : strtotime($this->request['start']);
        $ets = empty($this->request['end']) ? strtotime(date('Y-m-d',  strtotime("+45 day"))) : strtotime($this->request['end']);

        $uid = $this->adption['userid'];
        $type = $this->adption['type'];

        $lock = array();
        if ($type == Service_Data_Profile::USER_TYPE_TEACHER) {
            $lists = $this->getTeacherData($uid, $sts, $ets, $lock);
        } else {
            $lists = $this->getStudentData($uid, $sts, $ets);
        }
        
        echo json_encode($this->formatBase($lists, $lock, $type));
        exit;
    }

    // 获取教师数据
    private function getTeacherData ($uid, $sts, $ets, &$lock) {
        $serviceData = new Service_Data_Schedule();
        $conds = array(
            sprintf("start_time >= %d", $sts),
            sprintf("end_time <= %d", $ets),
            sprintf("teacher_uid = %d", intval($uid))
        );
        $lists = $serviceData->getListByConds($conds);

        // 锁定时间
        $conds = array(
            sprintf("start_time >= %d", $sts),
            sprintf("end_time <= %d", $ets),
            sprintf("uid = %d", intval($uid))
        ) ;
        $serviceData = new Service_Data_Lock();
        $lock = $serviceData->getListByConds($conds);

        if (empty($lists)) {
            return array();
        }
        return $lists;
    }

    // 获取学生数据
    private function getStudentData ($uid, $sts, $ets) {
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
        return $lists;
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
            if ($type == Service_Data_Profile::USER_TYPE_STUDENT) {
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
            $tmp['start']   = date("Y-m-d H:i:s",$item['start_time']);
            $tmp['end']     = date("Y-m-d H:i:s",$item['end_time']);
            if ($item['state'] == Service_Data_Schedule::SCHEDULE_DONE) {
                $tmp['color'] = "#2a8041";    
            }

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
            $scheduleState = $item['state'] == Service_Data_Schedule::SCHEDULE_ABLE ? "待上课" : "已下课";
            $subjectName = sprintf("%s/%s", $subjectParentInfos[$subjectParentId]['name'], $subjectInfo[$item['subject_id']]['name']);
            if ($type == Service_Data_Profile::USER_TYPE_TEACHER) {
		        $duration = sprintf("%.2f", ($item['end_time'] - $item['start_time']) / 3600) . "小时";
                $tmp['title'] = sprintf("%s %s %s %s %s", $duration, $groupInfos[$item['group_id']]['name'], $subjectName, $areaName, $scheduleState);  
            } else {
                $tmp['title'] = sprintf("%s %s %s %s", $subjectName, $userInfos[$item['teacher_uid']]['nickname'], $areaName, $scheduleState);
            }

            $result[] = $tmp;            
        }

        if (!empty($lock)) {
            foreach ($lock as $item) {
                $result[] = array(
                    "title"     => "教师锁定时间",
                    "start"     => date("Y-m-d H:i:s",$item['start_time']),
                    "end"       => date("Y-m-d H:i:s",$item['end_time']),
                    "color"     => "#123456"
                );
            }
        }

        return $result;
    }
}
