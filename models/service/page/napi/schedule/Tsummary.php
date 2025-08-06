<?php

class Service_Page_Napi_Schedule_Tsummary extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkTeacher()) {
            throw new Zy_Core_Exception(405, "无权限");
        }

        $result = array(
            'last_month_total'      => 0,
            'current_month_total'   => 0,
            'next_month_total'      => 0,
            'week_schedule_list'    => array(),
        );

        $serviceData = new Service_Data_Schedule();

        $now = time();

        $preSts = strtotime(date('Y-m-d', strtotime(date('Y-m-01', $now) . ' -1 month')));
        $preEts = $nowSts = strtotime(date('Y-m-1', $now));
        $nowEts = $nextSts = strtotime(date('Y-m-d', strtotime(date('Y-m-01', $now) . ' +1 month')));
        $nextEts = $ets = strtotime(date('Y-m-d', strtotime(date('Y-m-01', $now) . ' +2 month')));

        //本周
        $weekSts = strtotime('monday this week', $now);
        $weekEts = strtotime('monday next week', $now); // 减去一天（86400秒）

        // 三个月
        $conds = array(
            sprintf("start_time >= %d", $preSts),
            sprintf("end_time <= %d", $nextEts),
            sprintf("teacher_uid = %d", intval($this->adption['userid']))
        );
        $append = array(
            "order by start_time",
        );
        $lists = $serviceData->getListByConds($conds, array(), null, $append);

        // 规避2个订单同一个id, 
        $lists = array_column($lists, null, "schedule_id");
        $weekList = array();

        if (!empty($lists)) {
            foreach ($lists as $scheduleId => $item) {
                $duration = $item['end_time'] - $item['start_time'];
                //上个月
                if ($item["start_time"] >= $preSts && $item["end_time"] <= $preEts) {
                    $result["last_month_total"] += $duration;
                } else if ($item["start_time"] >= $nowSts && $item["end_time"] <= $nowEts) {
                    $result["current_month_total"] += $duration;
                } else if ($item["start_time"] >= $nextSts && $item["end_time"] <= $nextEts) {
                    $result["next_month_total"] += $duration;
                }

                // 本周
                if ($item["start_time"] >= $now && $item["end_time"] < $weekEts) {
                    $weekList[] = $item;
                }
            }

            $result['current_month_total'] = Zy_Helper_Utils::formatDurationForTime($result['current_month_total']);
            $result['next_month_total'] = Zy_Helper_Utils::formatDurationForTime($result['next_month_total']);
            $result['last_month_total'] = Zy_Helper_Utils::formatDurationForTime($result['last_month_total']);
            $result['week_schedule_list'] = $this->formatBase($weekList);
        }

        return $result;
    }

    // 格式化
    public function formatBase($lists) {
        if (empty($lists)) {
            return array();
        }
        
        $groupIds       = Zy_Helper_Utils::arrayInt($lists, "group_id");
        $subjectIds     = Zy_Helper_Utils::arrayInt($lists, "subject_id");

        $serviceSubject = new Service_Data_Subject();
        $subjectInfo = $serviceSubject->getListByConds(array('id in ('.implode(',', $subjectIds).')'));
        $subjectInfo = array_column($subjectInfo, null, 'id');

        $serviceData = new Service_Data_Group();
        $groupInfos = $serviceData->getListByConds(array('id in ('.implode(',', $groupIds).')'));
        $groupInfos = array_column($groupInfos, null, 'id');
        
        $nowW = date("w");

        $result = array();
        foreach ($lists as $key => $item) {
            if (empty($groupInfos[$item['group_id']]['name'])) {
                continue;
            }
            if (empty($subjectInfo[$item['subject_id']]['name'])) {
                continue;
            }
            // 信息
            $groupName = $groupInfos[$item['group_id']]['name'];
            // 科目信息
            $subjectName = $subjectInfo[$item['subject_id']]['name'];

            $wtime = date("w", $item["start_time"]);
            $time = date("H:i", $item["start_time"]);
            $isSoon = 0;
            if ($nowW == $wtime) {
                $wtime = "今天";
                $isSoon = 1;
            } else {
                $wtime = $wtime == "0" ? "周日" : Service_Data_Schedule::WEEK_TIME[$wtime];
            }

            $result[] = array(
                "title" => $subjectName,
                "name" => $groupName,
                "date" => $wtime,
                "time" => $time,
                "is_soon" => $isSoon,
            );
        }
        return $result;
    }    
}