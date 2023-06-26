<?php

class Service_Page_Base_Page extends Zy_Core_Service{

    // 查询通过登录uid上课俩表, 
    public function execute () {
        if (!$this->checkTeacher() && !$this->checkStudent()) {
            return ;  
        }

        if ($this->checkTeacher()) {
            return $this->getTeacherData();
        } 
        if ($this->checkStudent()) {
            return $this->getStudentData();
        }
        return array();
    }

    private function getStudentData() {

        // 先查当前用户有哪些班级
        $serviceGroupMap = new Service_Data_User_Group();
        $groupMap = $serviceGroupMap->getGroupMapBySid($this->adption['userid']);
        if (empty($groupMap)) {
            return array();;
        }

        $groupIds = array();
        foreach ($groupMap as $item) {
            $groupIds[intval($item['group_id'])] = intval($item['group_id']);
        }
        $groupIds = array_values($groupIds);

        $serviceGroup = new Service_Data_Group();
        $groups = $serviceGroup->getListByConds(array(sprintf("id in (%s)", implode(",", $groupIds))));
        if (empty($groups)) {
            return array();
        }

        $serviceSchedule = new Service_Data_Schedule();
        $scheduleLists = $serviceSchedule->getLastDuration($groupIds);

        $result =array();
        foreach ($groups as &$item) {
            $lastDuration = $item['duration'];
            if (isset($scheduleLists[$item['id']])) {
                $lastDuration = $item['duration'] - $scheduleLists[$item['id']];
            }
            $result[] = array(
                "groupName" => $item['name'],
                'duration' => sprintf("%.2f课时", $item['duration']), 
                'lastDuration' => sprintf("%.2f课时", $lastDuration),
                'durationColor' => $lastDuration <= 0 ? "text-danger" : "text-white",
                'icon' => ["fa-bar-chart-o", "fa-clock-o", "fa-line-chart"][mt_rand(0,2)],
                'bg' => ["btn-c-gradient-2", "btn-c-gradient-3", "btn-c-gradient-4"][mt_rand(0,2)],
            );
        }
        return $result;
    }

    private function getTeacherData() {
        $sts = strtotime(date("Y-m-1"));
        $ets = strtotime(date('Y-m-d', strtotime('first day of next month')));

        $month = 0;
        $conds = array(
            "start_time >= " . $sts,
            "end_time < " . $ets,
            "teacher_id" => $this->adption["userid"],
        );
        $serviceSchedule = new Service_Data_Schedule();
        $lists = $serviceSchedule->getListByConds($conds, array('start_time', "end_time"));
        if (!empty($lists)) {
            foreach ($lists as $item) {
                $timeLength = ($item['end_time'] - $item['start_time']) / 3600;
                $month += $timeLength;
            }
        }

        $sts = strtotime(date('Y-m-d', strtotime(date('Y-m-01') . ' -1 month')));
        $ets = strtotime(date('Y-m-1'));
        $lastMonth = 0;
        $conds = array(
            "start_time >= " . $sts,
            "end_time < " . $ets,
            "teacher_id" => $this->adption["userid"],
        );
        $serviceSchedule = new Service_Data_Schedule();
        $lists = $serviceSchedule->getListByConds($conds, array('start_time', "end_time"));
        if (!empty($lists)) {
            foreach ($lists as $item) {
                $timeLength = ($item['end_time'] - $item['start_time']) / 3600;
                $lastMonth += $timeLength;
            }
        }
        return array(
            "month" => sprintf("%.2f课时", $month), 
            "lastMonth" => sprintf("%.2f课时", $lastMonth),
        );
    }
}