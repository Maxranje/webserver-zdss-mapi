<?php

// 排课列表
class Service_Page_Schedule_Pklists extends Zy_Core_Service{

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

        $pn = empty($this->request['page']) ? 1 : intval($this->request['page']);
        $rn = empty($this->request['perPage']) ? 20 : intval($this->request['perPage']);

        $pn = ($pn-1) * $rn;

        $id = empty($this->request['id']) ? "" : strval($this->request['id']);
        $groupId = empty($this->request['group_ids']) ? "" : strval($this->request['group_ids']);
        $teacherId = empty($this->request['teacher_id']) ? 0 : intval($this->request['teacher_id']);
        $areaId = empty($this->request['area_id']) ? 0 : intval($this->request['area_id']);
        $daterange = empty($this->request['daterange']) ? "" : $this->request['daterange'];
        $areaop = empty($this->request['area_op']) ? 0 : intval($this->request['area_op']);
        $status = empty($this->request['status']) || !in_array($this->request['status'], [1,2]) ? 0 : $this->request['status'];

        list($sts, $ets) = empty($daterange) ? array(0,0) : explode(",", $daterange);

        $columnIds = array();
        if ($teacherId > 0) {
            $serviceColumn = new Service_Data_Column();
            $columnIds = $serviceColumn->getColumnByTId($teacherId);
            if (empty($columnIds)) {
                return array();
            }
            $columnIds = array_column($columnIds, "id");
        }

        $serviceData = new Service_Data_Schedule();

        $conds = array();
        if (!empty($id)) {
            $id = explode(",", $id);
            foreach ($id as $k => $i) {
                $id[$k] = intval($i);
            }
            $conds[] = sprintf("id in (%s)", implode(",", $id));
        }
        if (!empty($groupId)) {
            $conds[] = sprintf("group_id in (%s)", $groupId);
        } 
        if (!empty($columnIds)) {
            $conds[] = sprintf('column_id in (%s)', implode(",", $columnIds));
        }
        if ($areaId > 0) {
            $conds[] = "area_id = ".$areaId;
        }
        if ($areaop > 0) {
            $conds[] = "area_op = ".$areaop;
        }
        if ($sts > 0) {
            $conds[] = "start_time >= ".$sts;
        }
        if ($ets > 0) {
            $conds[] = "end_time <= ".($ets + 1);
        }
        if ($status > 0) {
            $conds[] = "state = " . ($status == 1 ? 0 : 1) ;
        } else {
            $conds[] = "state !=3 ";
        }
        $arrAppends[] = 'order by start_time';
        if (empty($this->request['export'])) {
            $arrAppends[] = "limit {$pn} , {$rn}";
        }

        $lists = $serviceData->getListByConds($conds, false, NULL, $arrAppends);
        $total = $serviceData->getTotalByConds($conds);

        $sum_duration = 0;
        $lists = $this->formatBase($lists, $sum_duration);
        
        if (!empty($this->request['export'])) {
            $data = $this->formatExcel($lists);
            Zy_Helper_Utils::exportExcelSimple("Schedule", $data['title'], $data['lists']);
        }

        $result = array(
            'rows' => $lists,
            'total' => $total,
            'sum_duration' => $sum_duration > 0 ? $sum_duration / 3600 . "小时" : "-",
        );
        return $result;
    }

    private function formatBase ($lists, &$sum_duration) {
        if (empty($lists)) {
            return array();
        }

        // 初始化参数
        $uids           = array();
        $columnIds      = array();
        $groupIds       = array();
        $areaIds        = array();
        $roomIds        = array();
        foreach ($lists as $item) {
            $columnIds[intval($item['column_id'])] = intval($item['column_id']);
            $groupIds[intval($item['group_id'])] = intval($item['group_id']);
            $uids[intval($item['operator'])] = intval($item['operator']);
            
            // 获取校区id
            if (!empty($item['area_id'])) {
                $areaIds[intval($item['area_id'])] = intval($item['area_id']);
            }
            if (!empty($item['room_id'])) {
                $roomIds[intval($item['room_id'])] = intval($item['room_id']);
            }

            //区域管理者
            if (!empty($item['area_op'])) {
                $uids[intval($item['area_op'])] = intval($item['area_op']);
            }
        }
        $columnIds = array_values($columnIds);
        $groupIds = array_values($groupIds);
        $areaIds = array_values($areaIds);
        $roomIds = array_values($roomIds);

        // 获取教师名字
        $serviceColumn = new Service_Data_Column();
        $columnInfos = $serviceColumn->getListByConds(array('id in ('.implode(',', $columnIds).')'));
        $columnInfos = array_column($columnInfos, null, 'id');

        $subject_ids = array();
        foreach ($columnInfos as $c) {
            $uids[$c['teacher_id']] = intval($c['teacher_id']);
            $subject_ids[] = intval($c['subject_id']);
        }

        $serviceSubject = new Service_Data_Subject();
        $subjectInfo = $serviceSubject->getListByConds(array('id in ('.implode(',', $subject_ids).')'));
        $subjectInfo = array_column($subjectInfo, null, 'id');

        $serviceGroup = new Service_Data_Group();
        $groupInfos = $serviceGroup->getListByConds(array('id in ('.implode(",", $groupIds).')'));
        $groupInfos = array_column($groupInfos, null, 'id');

        $serviceGroupMap = new Service_Data_User_Group();
        $groupMapInfos = $serviceGroupMap->getListByConds(array('group_id in ('.implode(",", $groupIds).')'));

        // 如果班级只有一个学生要拿到学生生源地
        $groupMaps = array();
        foreach ($groupMapInfos as $map) {
            if (!isset($groupMaps[$map['group_id']])) {
                $groupMaps[$map['group_id']] = array();
            }
            $groupMaps[$map['group_id']][] = intval($map['student_id']);
        }

        foreach ($groupMaps as $key => $value) {
            if (is_array($value) && count($value) == 1) {
                $uids[intval($value[0])] = intval($value[0]);
            }
        }
        $uids = array_values($uids);

        $serviceUser = new Service_Data_User_Profile();
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
        foreach ($lists as $key => &$item) {
            $item['isSuper'] = $this->checkSuper() ? 1 : 0;
            $item["week_time"] = $this->weekName[date("w", $item['start_time'])];
            $item['time_day'] = strtotime(date("Y-m-d", $item['start_time']));
            $item['time_range'] = sprintf("%s,%s", date("H:i", $item['start_time']), date("H:i", $item['end_time']));
            $item['range_time'] = date('Y-m-d H:i', $item['start_time']) . "~".date('H:i', $item['end_time']);
            $item['duration'] = $item['end_time'] - $item['start_time'];
            $sum_duration += $item['duration'];
            $item['duration'] = sprintf("%.2f小时",  $item['duration'] / 3600);

            $item['create_time'] = date('Y-m-d H:i:s', $item['create_time']);
            if (empty($columnInfos[$item['column_id']]['teacher_id'])
                || empty($columnInfos[$item['column_id']]['subject_id'])) {
                unset($lists[$key]);
                continue;
            }
            $tid = $columnInfos[$item['column_id']]['teacher_id'];
            $sid = $columnInfos[$item['column_id']]['subject_id'];
            if (empty($userInfos[$tid]['nickname'])
                || empty($subjectInfo[$sid]['name'])
                || empty($groupInfos[$item['group_id']]['name'])) {
                unset($lists[$key]);
                continue;
            }
            $item['s_t_id'] = $sid . "_" . $tid;
            $item['teacher_name'] = $userInfos[$tid]['nickname'];
            $item['subject_name'] = $subjectInfo[$sid]['name'];
            $item['group_name'] = $groupInfos[$item['group_id']]['name'];
            
            // 校区信息
            $item['area_name'] = "";
            $item['room_name'] = "";
            $item['area_mark'] = "";
            if (!empty($item['area_id']) && !empty($areaInfos[$item['area_id']]['name'])) {
                $item['area_name'] = $areaInfos[$item['area_id']]['name'];
                if (!empty($item['room_id']) && !empty($roomInfos[$item['room_id']]['name'])) {
                    $item['room_name'] = $roomInfos[$item['room_id']]['name'];
                } else {
                    $item['room_name'] = "无教室";
                }
                // 学生线上课, 教师线下
                $ext = empty($item['ext']) ? array() : json_decode($item['ext'], true);
                if (isset($ext['is_online']) && $ext['is_online'] == 1) {
                    $item['area_mark'] = "线上";
                }                
                if (empty($this->request['export'])) {
                    $item['area_name'] = sprintf("%s(%s)", $item['area_name'], $item['room_name']);
                    if (!empty($item['area_mark'])) {
                        $item['area_name'] .= sprintf("(%s)", $item['area_mark']);
                    }
                    unset($item['room_name']);
                    unset($item['area_mark']);
                }
            }

            // 学生数量是不是大于1个
            $item['muilt_scount'] = 0;
            $item['birthplace'] = "-";
            if (!empty($groupMaps[$item['group_id']])) {
                $item['muilt_scount'] = count($groupMaps[$item['group_id']]);
                if ($item['muilt_scount'] == 1) {
                    $suid = $groupMaps[$item['group_id']][0];
                    if (!empty($userInfos[$suid]['birthplace'])) {
                        $item['birthplace'] = $userInfos[$suid]['birthplace'];
                    }
                }
            } 



            $item['area_op_name'] = "-";
            if (!empty($userInfos[$item['area_op']]['nickname'])) {
                $item['area_op_name'] = $userInfos[$item['area_op']]['nickname'];
            }
            if (empty($item['area_op'])) {
                $item['area_op'] = "";
            }

            $item['operator_name']= $userInfos[$item['operator']]['nickname'];
            $item['stateInfo'] = $item['state'] == 1 ? "未结算" : "已结算";
            
	    }
	    $lists = array_values($lists);
        return $lists;
    }

    private function formatExcel($lists) {
        $result = array(
            'title' => array('ID', '教师名', '班级名', '课程名', '校区', '教室', '校区说明',  '排课人员', '生源地', '区域管理', '状态', '星期', '时长', '时间', '创建时间'),
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
                $item['birthplace'],
                $item['area_op_name'],
                $item['stateInfo'],
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
