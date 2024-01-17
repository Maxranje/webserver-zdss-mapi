<?php

// 排课列表
class Service_Page_Schedule_Lists extends Zy_Core_Service{

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

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $ids        = empty($this->request['ids']) ? "" : strval($this->request['ids']);
        $groupIds   = empty($this->request['group_ids']) ? "" : strval($this->request['group_ids']);
        $teacherUid = empty($this->request['teacher_uid']) ? 0 : intval($this->request['teacher_uid']);
        $areaId     = empty($this->request['area_id']) ? 0 : intval($this->request['area_id']);
        $orderId    = empty($this->request['order_id']) ? 0 : intval($this->request['order_id']);
        $daterange  = empty($this->request['daterange']) ? "" : $this->request['daterange'];
        $areaOp     = empty($this->request['area_operator']) ? 0 : intval($this->request['area_operator']);
        $state      = empty($this->request['state']) || !in_array($this->request['state'], [1,2]) ? 0 : $this->request['state'];
        $isExport   = empty($this->request['is_export']) ? false : true;
        $pn         = empty($this->request['page']) ? 1 : intval($this->request['page']);
        $rn         = empty($this->request['perPage']) ? 20 : intval($this->request['perPage']);
        $pn         = ($pn-1) * $rn;

        list($sts, $ets) = empty($daterange) ? array(0,0) : explode(",", $daterange);

        $conds = array();
        if (!empty($ids)) {
            $conds[] = sprintf("id in (%s)", $ids);
        }
        if (!empty($groupIds)) {
            $conds[] = sprintf("group_id in (%s)", $groupIds);
        } 
        if ($teacherUid > 0) {
            $conds[] = sprintf("teacher_uid = %d", $teacherUid);
        }
        if ($areaId > 0) {
            $conds[] = sprintf("area_id = %d", $areaId);
        }
        if ($areaOp > 0) {
            $conds[] = sprintf("area_operator = %d", $areaOp);
        }
        if ($sts > 0) {
            $conds[] = sprintf("start_time >= %d", $sts);
        }
        if ($ets > 0) {
            $conds[] = sprintf("end_time >= %d", ($ets + 1));
        }
        if ($state > 0) {
            $conds[] = sprintf("state = %d", $state);
        }
        if ($orderId > 0) {
            $serviceData = new Service_Data_Curriculum();
            $schdules = $serviceData->getListByConds(array('order_id' => $orderId));
            if (empty($schdules)) {
                return array();
            }
            $schdules = Zy_Helper_Utils::arrayInt($schdules, 'schedule_id');
            $conds[] = sprintf("id in (%s)", implode(",", $schdules));
        }

        $arrAppends[] = 'order by start_time';
        if (!$isExport) {
            $arrAppends[] = "limit {$pn} , {$rn}";
        }

        $serviceData = new Service_Data_Schedule();
        $lists = $serviceData->getListByConds($conds, false, NULL, $arrAppends);
        $lists = $this->formatDefault($lists, $duration);
        
        if ($isExport) {
            $data = $this->formatExcel($lists);
            Zy_Helper_Utils::exportExcelSimple("Schedule", $data['title'], $data['lists']);
        }

        $total = $serviceData->getTotalByConds($conds);
        return array(
            'rows' => $lists,
            'total' => $total,
            'duration' => $duration > 0 ? $duration / 3600 . "小时" : "-",
        );
    }

    private function formatDefault ($lists, &$sum_duration) {
        if (empty($lists)) {
            return array();
        }

        // 初始化参数
        $scheduleIds = Zy_Helper_Utils::arrayInt($lists, "id");
        $teacherUids = Zy_Helper_Utils::arrayInt($lists, "teacher_uid");
        $columnIds = Zy_Helper_Utils::arrayInt($lists, "column_id");
        $groupIds = Zy_Helper_Utils::arrayInt($lists, "group_id");
        $areaIds = Zy_Helper_Utils::arrayInt($lists, "area_id");
        $roomIds = Zy_Helper_Utils::arrayInt($lists, "room_id");
        $operators = Zy_Helper_Utils::arrayInt($lists, "operator");
        $areaOps = Zy_Helper_Utils::arrayInt($lists, "area_operator");
        $subjectIds = Zy_Helper_Utils::arrayInt($lists, "subject_id");
        
        $uids = array_unique(array_merge($operators, $areaOps, $teacherUids));

        // 获取科目信息
        $serviceCurrent = new Service_Data_Curriculum();
        $orderCountInfos = $serviceCurrent->getOrderCountBySchedule($scheduleIds);
        $orderCountInfos = array_column($orderCountInfos, null, "schedule_id");

        $serviceColumn = new Service_Data_Column();
        $columnInfos = $serviceColumn->getListByConds(array('id in ('.implode(',', $columnIds).')'));
        $columnInfos = array_column($columnInfos, null, "id");

        $serviceSubject = new Service_Data_Subject();
        $subjectInfo = $serviceSubject->getSubjectByIds($subjectIds);
        $subjectInfo = array_column($subjectInfo, null, 'id');

        $subjectParentIds = Zy_Helper_Utils::arrayInt($subjectInfo, "parent_id");
        $subjectParentInfos = $serviceSubject->getSubjectByIds($subjectParentIds);
        $subjectParentInfos = array_column($subjectParentInfos, null, 'id');

        $serviceGroup = new Service_Data_Group();
        $groupInfos = $serviceGroup->getListByConds(array('id in ('.implode(",", $groupIds).')'));
        $groupInfos = array_column($groupInfos, null, 'id');

        $serviceUser = new Service_Data_Profile();
        $userInfos = $serviceUser->getListByConds(array('uid in ('.implode(',', $uids).')'));
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
        
        $isModeUpdate = $this->isModeAble(Service_Data_Roles::ROLE_MODE_SCHEDULE_UPDATE);
        $isModeDelete = $this->isModeAble(Service_Data_Roles::ROLE_MODE_SCHEDULE_DELETE);
        
        $sum_duration = 0;
        $result = array();
        foreach ($lists as $key => &$item) {
            if (empty($userInfos[$item['teacher_uid']]['nickname'])){
                continue;
            }
            if (empty($columnInfos[$item['column_id']]['subject_id'])){
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
            if (empty($groupInfos[$item['group_id']]['name'])) {
                continue;
            }

            $item['is_super']   = $this->checkSuper() ? 1 : 0;
            $item['is_u']       = $isModeUpdate ? 1 : 0;
            $item['is_d']       = $isModeDelete ? 1 : 0;
            $item["week_time"]  = $this->weekName[date("w", $item['start_time'])];
            $item['time_day']   = strtotime(date("Y-m-d", $item['start_time']));
            $item['time_range'] = sprintf("%s,%s", date("H:i", $item['start_time']), date("H:i", $item['end_time']));
            $item['range_time'] = date('Y-m-d H:i', $item['start_time']) . "~".date('H:i', $item['end_time']);
            $item['duration'] = $item['end_time'] - $item['start_time'];
            $sum_duration += $item['duration'];
            $item['duration'] = sprintf("%.2f小时",  $item['duration'] / 3600);

            $item['create_time'] = date('Y-m-d H:i:s', $item['create_time']);
            $item['state']          = intval($item['state']);
            $item['s_t_id']         = sprintf("%d_%d", $item['subject_id'], $item['teacher_uid']);
            $item['teacher_name']   = $userInfos[$item['teacher_uid']]['nickname'];
            $item['subject_name']   = sprintf("%s / %s", $subjectParentInfos[$subjectParentId]['name'], $subjectInfo[$item['subject_id']]['name']);
            $item['group_name']     = $groupInfos[$item['group_id']]['name'];
            $item['order_count']    = empty($orderCountInfos[$item['id']]['count']) ? 0 : intval($orderCountInfos[$item['id']]['count']);
            
            // 校区信息
            $item['area_name'] = "";
            $item['room_name'] = "";
            $item['area_mark'] = "";
            $item["a_r_id"] = ""; 
            if (!empty($item['area_id']) && !empty($areaInfos[$item['area_id']]['name'])) {
                $item['area_name'] = $areaInfos[$item['area_id']]['name'];
                $item['a_r_id'] = $item['area_id'];
                if (!empty($item['room_id']) && !empty($roomInfos[$item['room_id']]['name'])) {
                    $item['room_name'] = $roomInfos[$item['room_id']]['name'];
                    $item['a_r_id'] .= "_" . $item['room_id'];
                } else {
                    $item['room_name'] = "无教室";
                }
                // 学生线上课, 教师线下
                $ext = empty($item['ext']) ? array() : json_decode($item['ext'], true);
                if (isset($ext['is_online']) && $ext['is_online'] == 1) {
                    $item['area_mark'] = "线上";
                }                
                if (empty($this->request['is_export'])) {
                    $item['area_name'] = sprintf("%s(%s)", $item['area_name'], $item['room_name']);
                    if (!empty($item['area_mark'])) {
                        $item['area_name'] .= sprintf("(%s)", $item['area_mark']);
                    }
                    unset($item['room_name']);
                    unset($item['area_mark']);
                }
            }

            $item['area_op_name'] = "-";
            if (!empty($userInfos[$item['area_operator']]['nickname'])) {
                $item['area_op_name'] = $userInfos[$item['area_operator']]['nickname'];
            }
            if (empty($item['area_operator'])) {
                $item['area_operator'] = "";
            }

            $item['operator_name']= empty($userInfos[$item['operator']]['nickname']) ? "" :$userInfos[$item['operator']]['nickname'];
            $result[] = $item;
	    }
	    return array_values($result);
    }

    private function formatExcel($lists) {
        $result = array(
            'title' => array('ID', '教师名', '班级名', '课程名', '校区', '教室', '校区说明',  '排课人员', '助教', '状态', '星期', '时长', '时间', '创建时间'),
            'lists' => array(),
        );
        
        foreach ($lists as $item) {
            $tmp = array(
                $item['id'],
                $item['teacher_name'],
                $item['group_name'],
                $item['subject_name'],
                $item['area_name'],
                $item['room_name'],
                $item['area_mark'],
                $item['operator_name'],
                $item['area_op_name'],
                $item['state'] == 1 ? "待开始" : "已结算",
                $item['week_time'],
                $item['duration'],
                $item['range_time'],
                $item['create_time'],
            );
            $result['lists'][] = $tmp;
        }
        return $result;

    }
}
