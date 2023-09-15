<?php

class Service_Page_Api_Page extends Zy_Core_Service{

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
        $result = array(
            'lm_duration' => 0,
            'nm_duration' => 0,
            'all_duration' => 0,
            'able_duration' => 0,
        );

        $serviceData = new Service_Data_Curriculum();

        // 上个月
        $sts = strtotime(date('Y-m-d', strtotime(date('Y-m-01') . ' -1 month')));
        $ets = strtotime(date('Y-m-1'));
        $conds = array(
            sprintf("start_time >= %d", $sts),
            sprintf("end_time <= %d", $ets),
            sprintf("student_uid = %d", intval($this->adption['userid']))
        );
        $lists = $serviceData->getListByConds($conds);

        // 规避2个订单同一个id, 
        $lists = array_column($lists, null, "schedule_id");
        if (!empty($lists)) {
            foreach ($lists as $scheduleId => $item) {
                $result['lm_duration'] += $item['end_time'] - $item['start_time'];
            }
        }


        // 下个月
        $sts = strtotime(date('Y-m-d', strtotime(date('Y-m-01') . ' +1 month')));
        $ets = strtotime(date('Y-m-d', strtotime(date('Y-m-01') . ' +2 month')));
        $conds = array(
            sprintf("start_time >= %d", $sts),
            sprintf("end_time <= %d", $ets),
            sprintf("student_uid = %d", intval($this->adption['userid']))
        );
        $lists = $serviceData->getListByConds($conds);

        if (!empty($lists)) {
            foreach ($lists as $item) {
                $result['nm_duration'] += $item['end_time'] - $item['start_time'];
            }
        }

        // 本月
        $sts = strtotime(date("Y-m-1"));
        $ets = strtotime(date('Y-m-d', strtotime('first day of next month')));
        $conds = array(
            sprintf("start_time >= %d", $sts),
            sprintf("end_time <= %d", $ets),
            sprintf("student_uid = %d", intval($this->adption['userid']))
        );
        $lists = $serviceData->getListByConds($conds);

        // 规避2个订单同一个id, 
        $lists = array_column($lists, null, "schedule_id");
        if (!empty($lists)) {
            foreach ($lists as $scheduleId => $item) {
                if ($item['state'] == Service_Data_Schedule::SCHEDULE_ABLE) {
                    $result['able_duration'] += $item['end_time'] - $item['start_time'];
                }
                $result['all_duration'] += $item['end_time'] - $item['start_time'];
            }
        }



        $result['all_duration'] = Zy_Helper_Utils::formatDurationForTime($result['all_duration']);
        $result['able_duration'] = Zy_Helper_Utils::formatDurationForTime($result['able_duration']);
        $result['lm_duration'] = Zy_Helper_Utils::formatDurationForTime($result['lm_duration']);
        $result['nm_duration'] = Zy_Helper_Utils::formatDurationForTime($result['nm_duration']);
        return $result;
    }

    private function getTeacherData() {
        $result = array(
            'lm_duration' => 0,
            'nm_duration' => 0,
            'all_duration' => 0,
            'able_duration' => 0,
        );

        $serviceData = new Service_Data_Schedule();

        // 上个月
        $sts = strtotime(date('Y-m-d', strtotime(date('Y-m-01') . ' -1 month')));
        $ets = strtotime(date('Y-m-1'));
        $conds = array(
            sprintf("start_time >= %d", $sts),
            sprintf("end_time <= %d", $ets),
            sprintf("teacher_uid = %d", intval($this->adption['userid']))
        );
        $lists = $serviceData->getListByConds($conds);

        if (!empty($lists)) {
            foreach ($lists as $item) {
                $result['lm_duration'] += $item['end_time'] - $item['start_time'];
            }
        }

        // 下个月
        $sts = strtotime(date('Y-m-d', strtotime(date('Y-m-01') . ' +1 month')));
        $ets = strtotime(date('Y-m-d', strtotime(date('Y-m-01') . ' +2 month')));
        $conds = array(
            sprintf("start_time >= %d", $sts),
            sprintf("end_time <= %d", $ets),
            sprintf("teacher_uid = %d", intval($this->adption['userid']))
        );
        $lists = $serviceData->getListByConds($conds);

        if (!empty($lists)) {
            foreach ($lists as $item) {
                $result['nm_duration'] += $item['end_time'] - $item['start_time'];
            }
        }

        // 本月
        $sts = strtotime(date("Y-m-1"));
        $ets = strtotime(date('Y-m-d', strtotime('first day of next month')));
        $conds = array(
            sprintf("start_time >= %d", $sts),
            sprintf("end_time <= %d", $ets),
            sprintf("teacher_uid = %d", intval($this->adption['userid']))
        );
        $lists = $serviceData->getListByConds($conds);

        if (!empty($lists)) {
            foreach ($lists as $item) {
                if ($item['state'] == Service_Data_Schedule::SCHEDULE_ABLE) {
                    $result['able_duration'] += $item['end_time'] - $item['start_time'];
                }
                $result['all_duration'] += $item['end_time'] - $item['start_time'];
            }
        }

        $result['all_duration'] = Zy_Helper_Utils::formatDurationForTime($result['all_duration']);
        $result['able_duration'] = Zy_Helper_Utils::formatDurationForTime($result['able_duration']);
        $result['lm_duration'] = Zy_Helper_Utils::formatDurationForTime($result['lm_duration']);
        $result['nm_duration'] = Zy_Helper_Utils::formatDurationForTime($result['nm_duration']);
        return $result;
    }
}