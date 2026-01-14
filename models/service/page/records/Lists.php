<?php

class Service_Page_Records_Lists extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $pn             = empty($this->request['page']) ? 0 : intval($this->request['page']);
        $rn             = empty($this->request['perPage']) ? 0 : intval($this->request['perPage']);
        $studentUids    = empty($this->request['student_uids']) ? array() : Zy_Helper_Utils::arrayInt(explode(",", trim($this->request['student_uids'])));
        $teacherUids    = empty($this->request['teacher_uids']) ? array() : Zy_Helper_Utils::arrayInt(explode(",", trim($this->request['teacher_uids'])));
        $bpid           = empty($this->request['bpid']) ? 0 : intval($this->request['bpid']);
        $scheduleId     = empty($this->request['schedule_id']) ? 0 : intval($this->request['schedule_id']);
        $groupIds       = empty($this->request['group_ids']) ? array() : Zy_Helper_Utils::arrayInt(explode(",", trim($this->request['group_ids'])));
        $category       = empty($this->request['category']) ? 0 : intval($this->request['category']);
        $dataRange      = empty($this->request['daterangee']) ? array() : explode(",", $this->request['daterangee']);
        $isExport       = empty($this->request['is_export']) ? false : true;

        $pn = ($pn-1) * $rn;
        $uids = array_merge($studentUids, $teacherUids);
        $uids = array_unique($uids);

        if ($this->checkPartner() ) {
            $bpid = $this->getPartnerBpid($this->adption['userid']);
        }

        $serviceRecords = new Service_Data_Records();

        $lists = array();
        $total = 0;
        if ($bpid > 0 ) {
            $lists = $serviceRecords->getListByBpid($uids, $bpid, $scheduleId, $groupIds, $category, $dataRange, $pn, $rn);
            if (!$isExport) {
                $total = $serviceRecords->getTotalByBpid($uids, $bpid, $scheduleId, $groupIds, $category, $dataRange);
            }
        } else {
            $conds = array();
            if (!empty($uids)) {
                $conds[] = sprintf("uid in (%s)", implode(",", $uids));
            }
            if ($category > 0) {
                $conds['category'] = $category;
            }
            if ($scheduleId > 0) {
                $conds['schedule_id'] = $scheduleId;
            }
            if (!empty($groupIds)) {
                $conds[] = sprintf("group_id in (%s)", implode(",", $groupIds));
            }
            if (!empty($dataRange)) {
                $conds[] = sprintf("create_time >= %d", $dataRange[0]);
                $conds[] = sprintf("create_time <= %d", ($dataRange[1] + 1));
            }
    
            $arrAppends[] = 'order by id desc';
            if (!$isExport) {
                $arrAppends[] = "limit {$pn} , {$rn}";
            }
    
            $lists = $serviceRecords->getListByConds($conds, false, NULL, $arrAppends);
            if (!$isExport) {
                $total = $serviceRecords->getTotalByConds($conds);
            }
        }
        if ($isExport && count($lists) > 2000) {
            throw new Zy_Core_Exception(405, "操作失败, 受服务限制, 导出数据限制2000条, 请选择条件缩小查询范围");
        }

        $lists = $this->formatBase($lists);
        if ($isExport) {
            $data = $this->formatExcel($lists);
            Zy_Helper_Utils::exportExcelSimple("payrecords", $data['title'], $data['lists']);
        }
        return array(
            'rows' => $lists,
            'total' => $total,
        );
    }

    private function formatBase($lists) {

        if (empty($lists)) {
            return array();
        }

        $operator = Zy_Helper_Utils::arrayInt($lists, 'operator');
        $groupIds = Zy_Helper_Utils::arrayInt($lists, 'group_id');
        $uids = Zy_Helper_Utils::arrayInt($lists, 'uid');
        $scheduleIds = Zy_Helper_Utils::arrayInt($lists, "schedule_id");

        $uids = array_unique(array_merge($uids, $operator));

        $serviceUsers = new Service_Data_Profile();
        $userInfos = $serviceUsers->getUserInfoByUids($uids);
        $userInfos = array_column($userInfos, null, "uid");

        $birthplaceIds = Zy_Helper_Utils::arrayInt($userInfos, "bpid");
        $serviceBirthplace = new Service_Data_Birthplace();
        $birthplace = $serviceBirthplace->getBirthplaceByIds($birthplaceIds);
        $birthplace = array_column($birthplace, null, "id");

        $serviceGroup = new Service_Data_Group();
        $groupInfos = $serviceGroup->getListByConds(array(sprintf("id in (%s)", implode(",", $groupIds))));
        $groupInfos = array_column($groupInfos, null, "id");

        $serviceSchedule = new Service_Data_Schedule();
        $scheduleInfos = $serviceSchedule->getScheduleByIds($scheduleIds);
        $scheduleInfos = array_column($scheduleInfos, null, "id");

        foreach ($lists as &$item) {
            if (empty($userInfos[$item['uid']]['nickname'])) {
                continue;
            }
            $duration = "";
            $scheduleTime = "";
            if (!empty($scheduleInfos[$item['schedule_id']])) {
                $duration = sprintf("%.2f", ($scheduleInfos[$item['schedule_id']]['end_time'] - $scheduleInfos[$item['schedule_id']]['start_time']) / 3600);
                $scheduleTime = sprintf("%s~%s", date("Y-m-d H:i",$scheduleInfos[$item['schedule_id']]['start_time']), date("H:i",$scheduleInfos[$item['schedule_id']]['end_time']));
            }

            $ext = empty($item['ext']) ? array() : json_decode($item['ext'], true);

            $item['type']           = $item['type'] == Service_Data_Profile::USER_TYPE_STUDENT ? "学员" : "教师";
            $item['nickname']       = $userInfos[$item['uid']]['nickname'];
            $item['operator']       = empty($userInfos[$item['operator']]['nickname']) ? "" : $userInfos[$item['operator']]['nickname'];
            $item['create_time']    = date("Y年m月d日 H:i:s", $item['create_time']);
            $item['update_time']    = date("Y年m月d日 H:i:s", $item['update_time']);
            $item['group_name']     = empty($groupInfos[$item['group_id']]['name']) ? "-" : $groupInfos[$item['group_id']]['name'];
            $item['birthplace']     = empty($birthplace[$userInfos[$item['uid']]['bpid']]['name']) ? "" :$birthplace[$userInfos[$item['uid']]['bpid']]['name'];
            $item['money_info']     = sprintf("%.2f元", $item['money'] / 100);
            $item['order_id']       = empty($item['order_id']) ? "-" : $item['order_id'];
            $item['isfree']         = "0";
            $item['is_abroadplan']  = empty($ext["order"]["abroadplan_id"]) ? "-" : "是";
            $item['duration']       = $duration ;
            $item["schedule_time"]  = empty($scheduleTime) ? "-" : $scheduleTime;
            if (isset($ext['order']) && !empty($ext['order']['isfree'])) {
                $item['isfree'] = "1";
                $item['money_info'] = "0.00元";
            }
        }
        return $lists;
    }

    private function formatExcel($lists) {
        $result = array(
            'title' => array('操作日期', '排课日期', 'UID', '用户名', '用户类型', '状态', '场景', '排课ID', '金额(元)', '课时', "生源地", '班级', '订单ID', "计划订单","免费订单", '操作员'),
            'lists' => array(),
        );
        if (empty($lists)) {
            return $result;
        }
        
        foreach ($lists as $item) {
            if (empty($item) || empty($item["nickname"])) {
                continue;
            }
            $tmp = array(
                $item['update_time'],
                $item['schedule_time'],
                $item['uid'],
                $item['nickname'],
                $item['type'],
                $item['state'] == Service_Data_Records::RECORDS_NOMARL ? "正常" : "撤销",
                $item['category'] == Service_Data_Schedule::CATEGORY_STUDENT_PAID ? "学员消费" : "教师收入",
                $item['schedule_id'],
                $item['money_info'],
                $item['duration'],
                $item['birthplace'],
                $item['group_name'],
                $item['order_id'],
                $item['is_abroadplan'],
                $item['isfree'],
                $item['operator'],
            );
            $result['lists'][] = $tmp;
        }
        return $result;

    }
}