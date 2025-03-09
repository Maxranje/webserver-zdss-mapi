<?php

// 排课列表
class Service_Page_Schedule_Area_Lists extends Zy_Core_Service{

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

        $groupId        = empty($this->request['group_ids']) ? "" : strval($this->request['group_ids']);
        $teacherUid     = empty($this->request['teacher_uid']) ? 0 : intval($this->request['teacher_uid']);
        $areaIds        = empty($this->request['area_id']) ? array() : explode(",", trim($this->request['area_id']));
        $daterange      = empty($this->request['daterange']) ? "" : $this->request['daterange'];
        $areaOperator   = empty($this->request['area_operator']) ? 0 : intval($this->request['area_operator']);
        $orderDir       = empty($this->request['orderDir']) ? "desc" : trim($this->request['orderDir']);
        $orderBy        = empty($this->request['orderBy']) ? "" : trim($this->request['orderBy']);
        $isExport       = empty($this->request['is_export']) ? false : true;
        $pn             = empty($this->request['page']) ? 1 : intval($this->request['page']);
        $rn             = empty($this->request['perPage']) ? 20 : intval($this->request['perPage']);
        $pn             = ($pn-1) * $rn;

        list($sts, $ets) = empty($daterange) ? array(0,0) : explode(",", $daterange);

        $areaIds = Zy_Helper_Utils::arrayInt($areaIds);

        $conds = array(
            "state" => Service_Data_Schedule::SCHEDULE_ABLE,
        );
        if (!empty($groupId)) {
            $conds[] = sprintf("group_id in (%s)", $groupId);
        } 
        if ($teacherUid > 0) {
            $conds[] = sprintf('teacher_uid = %d', $teacherUid);
        }
        if (!empty($areaIds)) {
            $conds[] = sprintf('area_id in (%s)', implode(",", $areaIds));
        } 
        // 过滤无教室&线上校区的
        if (in_array(-1, $areaIds)) {
            $serviceArea = new Service_Data_Area();
            $onlineAreas = $serviceArea->getAreaListByConds(array('is_online' => Service_Data_Area::ONLINE));
            $onlineAreas = Zy_Helper_Utils::arrayInt($onlineAreas, "id");
            if (array_intersect($onlineAreas, $areaIds)) {
                throw new Zy_Core_Exception(405, "搜索条件不能既有过滤线上校区, 又有查询线上校区");
            }
            $conds[] = "room_id = 0";
            if (!empty($onlineAreas)) {
                $conds[] = sprintf("area_id not in (%s)", implode(",", $onlineAreas));
            }
        }
        if ($areaOperator > 0) {
            $conds[] = sprintf('area_operator = %d', $areaOperator);
        }
        if ($sts > 0) {
            $conds[] = sprintf('start_time >= %d', $sts);
        }
        if ($ets > 0) {
            $conds[] = sprintf('end_time <= %d', ($ets + 1));
        }

        $orderby = 'order by start_time';
        if ($orderBy == "teacher_name") {
            $orderby = "order by teacher_uid " . ($orderDir == "desc" ? "desc" : "asc");
        }
        if ($orderBy == "range_time") {
            $orderby = "order by start_time " . ($orderDir == "desc" ? "desc" : "asc");
        }
        if ($orderBy == "group_name") {
            $orderby = "order by group_id " . ($orderDir == "desc" ? "desc" : "asc");
        }
        $arrAppends[] = $orderby;

        if (!$isExport) {
            $arrAppends[] = "limit {$pn} , {$rn}";
        }

        $serviceData = new Service_Data_Schedule();
        $total = $serviceData->getTotalByConds($conds);
        if ($isExport && $total > 2000) {
            throw new Zy_Core_Exception(405, "操作失败, 受系统限制, 导出的数据不能超过2000条");
        }

        $lists = $serviceData->getListByConds($conds, false, NULL, $arrAppends);

        $lists = $this->formatBase($lists, $duration);
                
        if ($isExport) {
            $data = $this->formatExcel($lists);
            Zy_Helper_Utils::exportExcelSimple("AreaRoom", $data['title'], $data['lists']);
        }

        if (empty($lists)) {
            return array();
        }
        
        $result = array(
            'rows' => $lists,
            'total' => $total,
            'duration' => $duration > 0 ? $duration . "小时" : "-",
        );
        return $result;
    }

    private function formatBase ($lists, &$sum_duration) {
        if (empty($lists)) {
            return array();
        }

        // 初始化参数
        $teacherUids = Zy_Helper_Utils::arrayInt($lists, "teacher_uid");
        $groupIds = Zy_Helper_Utils::arrayInt($lists, "group_id");
        $areaIds = Zy_Helper_Utils::arrayInt($lists, "area_id");
        $roomIds = Zy_Helper_Utils::arrayInt($lists, "room_id");
        $operators = Zy_Helper_Utils::arrayInt($lists, "operator");
        $areaOps = Zy_Helper_Utils::arrayInt($lists, "area_operator");
        $subjectIds = Zy_Helper_Utils::arrayInt($lists, "subject_id");
        
        $uids = array_unique(array_merge($operators, $areaOps, $teacherUids));

        // 获取科目信息
        $serviceSubject = new Service_Data_Subject();
        $subjectInfo = $serviceSubject->getListByConds(array('id in ('.implode(',', $subjectIds).')'));
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
        
        $sum_duration = 0;
        $result = array();
        foreach ($lists as $key => &$item) {
            if (empty($userInfos[$item['teacher_uid']]['nickname'])){
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

            $item["week_time"]  = $this->weekName[date("w", $item['start_time'])];
            $item['time_day']   = strtotime(date("Y-m-d", $item['start_time']));
            $item['time_range'] = sprintf("%s,%s", date("H:i", $item['start_time']), date("H:i", $item['end_time']));
            $item['range_time'] = date('Y-m-d H:i', $item['start_time']) . "~".date('H:i', $item['end_time']);
            $item['duration'] = $item['end_time'] - $item['start_time'];
            $sum_duration += $item['duration'];
            $item['duration'] = sprintf("%.2f小时",  $item['duration'] / 3600);

            $item['create_time']    = date('Y-m-d H:i:s', $item['create_time']);
            $item['state']          = intval($item['state']);
            $item['s_t_id']         = sprintf("%d_%d", $item['subject_id'], $item['teacher_uid']);
            $item['teacher_name']   = $userInfos[$item['teacher_uid']]['nickname'];
            $item['subject_name']   = sprintf("%s / %s", $subjectParentInfos[$subjectParentId]['name'], $subjectInfo[$item['subject_id']]['name']);
            $item['group_name']     = $groupInfos[$item['group_id']]['name'];
            
            // 校区信息
            $item['area_name'] = "";
            $item['room_name'] = "";
            $item['area_mark'] = "";
            $item['is_online'] = 0;
            if (!empty($item['area_id']) && !empty($areaInfos[$item['area_id']]['name'])) {
                $item['area_name'] = $areaInfos[$item['area_id']]['name'];
                $item['a_r_id'] = $item['area_id'];
                if (!empty($item['room_id']) && !empty($roomInfos[$item['room_id']]['name'])) {
                    $item['room_name'] = $roomInfos[$item['room_id']]['name'];
                    $item['a_r_id'] .= "_".$item['room_id'];
                } else {
                    $item['room_name'] = "无教室";
                }
                // 学生线上课, 教师线下
                $ext = empty($item['ext']) ? array() : json_decode($item['ext'], true);
                if (isset($ext['is_online']) && $ext['is_online'] == 1) {
                    $item['area_mark'] = "线上";
                    $item['is_online'] = 1;
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

            $item['operator_name']= empty($userInfos[$item['operator']]['nickname']) ? "" : $userInfos[$item['operator']]['nickname'];
            $result[] = $item;
	    }
	    return array_values($result);
    }

    private function formatExcel($lists) {
        $result = array(
            'title' => array('ID', '时间', '教师名', '班级名', '课程名', "校区", "教室", "校区教室备注", '助教', '星期', '时长', '创建时间'),
            'lists' => array(),
        );
        
        foreach ($lists as $item) {
            $tmp = array(
                $item['id'],
                $item['range_time'],
                $item['teacher_name'],
                $item['group_name'],
                $item['subject_name'],
                $item['area_name'],
                $item['room_name'],
                $item['area_mark'],
                $item['area_op_name'],
                $item['week_time'],
                $item['duration'],
                $item['create_time'],
            );
            $result['lists'][] = $tmp;
        }
        return $result;

    }
}
