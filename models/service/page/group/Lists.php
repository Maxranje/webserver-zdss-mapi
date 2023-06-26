<?php

class Service_Page_Group_Lists extends Zy_Core_Service{

    public $serviceGroup;
    public $serviceUsers;
    public $serviceGroupMap;

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $arrOutput = array("lists" => array(), 'total' => 0);

        $pn = empty($this->request['page']) ? 0 : intval($this->request['page']);
        $rn = empty($this->request['perPage']) ? 0 : intval($this->request['perPage']);

        $pn = ($pn-1) * $rn;

        $name       = empty($this->request['name']) ? "" : $this->request['name'];
        $studentId  = empty($this->request['student_id']) ? 0 : intval($this->request['student_id']);
        $areaop     = empty($this->request['area_op']) ? 0 : intval($this->request['area_op']);
        $nickname   = empty($this->request['student_nickname']) ? "" : $this->request['student_nickname'];
        $isSelect   = empty($this->request['isSelect']) ? false : true;

        $this->serviceGroup = new Service_Data_Group();
        $this->serviceUsers = new Service_Data_User_Profile();
        $this->serviceGroupMap = new Service_Data_User_Group();

        $groupConds = array();
        if (!empty($nickname)) {
            $conds = array(
                "nickname like '%".$nickname."%'"
            );
            $students = $this->serviceUsers->getListByConds($conds);
            if (empty($students)) {
                return $arrOutput;
            }

            $studentUids = array();
            foreach ($students as $item) {
                $studentUids[intval($item['uid'])] = intval($item['uid']);
            }
            $studentUids = array_values($studentUids);

            $conds = array(
                sprintf("student_id in (%s)", implode(",", $studentUids)),
            );
            $groupMap = $this->serviceGroupMap->getListByConds($conds);
            if (empty($groupMap)) {
                return $arrOutput;
            }

            $groupIds = array();
            foreach ($groupMap as $item) {
                $groupIds[intval($item['group_id'])] = intval($item['group_id']);
            }
            $groupIds = array_values($groupIds);
            $groupConds[] = sprintf("id in (%s)", implode(",", $groupIds)); 
        }

        // 选择列表中的, 所以返回和正常返回不一样
        if ($isSelect && $studentId > 0) {
            $conds = array(
                "student_id" => $studentId,
            );

            $groupMap = $this->serviceGroupMap->getListByConds($conds);
            if (empty($groupMap)) {
                return array();
            }

            $groupIds = array();
            foreach ($groupMap as $item) {
                $groupIds[intval($item['group_id'])] = intval($item['group_id']);
            }
            $groupIds = array_values($groupIds);
            $groupConds[] = sprintf("id in (%s)", implode(",", $groupIds)); 
        }

        if (!empty($name)) {
            $groupConds[] = "name like '%".$name."%'";
        }

        if ($areaop > 0) {
            $groupConds[] = "area_op = " . $areaop;
        }

        $arrAppends[] = 'order by id desc';

        if (!$isSelect) {
            $arrAppends[] = "limit {$pn} , {$rn}";
        }

        $lists = $this->serviceGroup->getListByConds($groupConds, false, null, $arrAppends);
        if ($isSelect) {
            return $this->formatSelect($lists);
        }

        $total = $this->serviceGroup->getTotalByConds($groupConds);
        $lists = $this->formatBase($lists);
        return array(
            'rows' => $lists,
            'total' => $total,
        );
    }

    private function formatBase($lists) {
        if (empty($lists)) {
            return array();
        }

        $groupIds = array();
        $uids = array();
        foreach ($lists as $item) {
            $groupIds[intval($item['id'])] = intval($item['id']);
            $uids[intval($item['area_op'])] = intval($item['area_op']);
        }
        $groupIds = array_values($groupIds);

        $groupMapInfo = array();
        $conds = array(
            sprintf("group_id in (%s)", implode(",", $groupIds))
        );
        $groupMap = $this->serviceGroupMap->getListByConds($conds);
        foreach ($groupMap as $key => $item) {
            if (!isset($groupMapInfo[$item['group_id']])) {
                $groupMapInfo[$item['group_id']] = array();
            }
            $groupMapInfo[$item['group_id']][] = intval($item['student_id']);
            $uids[intval($item['student_id'])] = intval($item['student_id']);
        }
        $uids = array_values($uids);

        $conds = array(
            sprintf("uid in (%s)", implode(",", $uids))
        );
        $userInfos = $this->serviceUsers->getListByConds($conds);
        $userInfos = array_column($userInfos, null, 'uid');

        $serviceSchedule = new Service_Data_Schedule();
        $scheduleCount = $serviceSchedule->getLastDuration($groupIds);

        foreach ($lists as &$item) {
            $gInfo = empty($groupMapInfo[$item['id']]) ? array() : $groupMapInfo[$item['id']];
            $item['studentNames'] = array();
            $item['studentCount'] = count($gInfo);
            if (!empty($gInfo)) {
                foreach ($gInfo as $index => $values) {
                    if (!empty($userInfos[$values])) {
                        $item['students'][] = $userInfos[$values];
                        $item['studentNames'][] = $userInfos[$values]['nickname'];
                    }
                }
                if (!empty($item['students'])) {
                    $item['studentNames'] = implode(",", $item['studentNames']);     
                }
            }
            if (isset($scheduleCount[$item['id']])) {
                $item['lastDuration'] = $item['duration'] - $scheduleCount[$item['id']];
            } else {
                $item['lastDuration'] = $item['duration'];
            }
            $item['lastDurationInfo'] = $item['lastDuration'] . "课时";

            if ($item['lastDuration'] <= 0) {
                $item["progress"] = 0 ;
            } else {
                $item["progress"] = intval(($item['lastDuration']/ $item['duration']) * 100);
            }

            if (!empty($item['area_op']) && !empty($userInfos[$item['area_op']]['nickname'])){
                $item['area_op_name'] = $userInfos[$item['area_op']]['nickname'];
            }
            if (empty($item['area_op'])) {
                $item['area_op'] = "";
            }
        }
        return $lists;
    }

    private function formatSelect($lists) {
        $options = array();
        foreach ($lists as $item) {
            $options[] = array(
                'label' => $item['name'],
                'value' => $item['id'],
            );
        }
        return array('options' => array_values($options));
    }
}