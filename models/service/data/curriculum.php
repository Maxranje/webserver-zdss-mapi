<?php

class Service_Data_Curriculum {

    private $daoCurriculum ;

    public function __construct() {
        $this->daoCurriculum = new Dao_Curriculum () ;
    }

    // 根据scheudle获取order数量
    public function getOrderCountBySchedule($ids) {
        $conds = array(
            sprintf("schedule_id in (%s)", implode(",", $ids))
        );
        $field = array(
            "count(id) as count",
            "schedule_id",
        );
        $appends = array(
            "group by schedule_id"
        );
        $lists = $this->daoCurriculum->getListByConds($conds, $field, null, $appends);
        if (empty($lists)) {
            return array();
        }
        return $lists;
    }

    // 根据order_id 获取所有的schedule_课时数
    public function getScheduleTimeCountByOrder($ids) {
        $conds = array(
            sprintf("order_id in (%s)", implode(",", $ids)),
        );
        $lists = $this->getListByConds($conds);

        $result = array();
        foreach ($lists as $item) {
            if (!isset($result[$item['order_id']])) {
                $result[$item['order_id']] = array(
                    "able_count" => 0,
                    "disable_count" => 0,
                    "all_count" => 0,
                );
            }

            if ($item['state'] == Service_Data_Schedule::SCHEDULE_DONE) {
                $result[$item['order_id']]['disable_count'] += $item['end_time'] - $item['start_time'];
            }else {
                $result[$item['order_id']]['able_count'] += $item['end_time'] - $item['start_time'];
            }
            $result[$item['order_id']]['all_count'] += $item['end_time'] - $item['start_time'];
        }
        
        foreach ($result as $key => &$value) {
            $value['able_count'] = empty($value['able_count']) ? 0 : $value['able_count'] / 3600;
            $value['disable_count'] = empty($value['disable_count']) ? 0 : $value['disable_count'] / 3600;
            $value['all_count'] = empty($value['all_count']) ? 0 : $value['all_count'] / 3600;
        }

        return $result;
    }

       // group_id 获取所有的order
    public function getOrderListByGroup($ids) {
        $conds = array(
            sprintf("group_id in (%s)", implode(",", $ids))
        );
        $field = array(
            "count(id) as count",
            "order_id",
        );
        $appends = array(
            "group by order_id"
        );
        $lists = $this->daoCurriculum->getListByConds($conds, $field, null, $appends);
        if (empty($lists)) {
            return array();
        }
        return $lists;
    }

    // 创建
    public function create ($profile) {
        $this->daoCurriculum->startTransaction();
        foreach ($profile['schedules'] as $item) {
            $p1 = array(
                "schedule_id"       => intval($item["id"]),
                "student_uid"       => intval($profile['order_info']["student_uid"]),
                "order_id"          => intval($profile['order_info']["order_id"]),
                "column_id"         => intval($item["column_id"]),
                "group_id"          => intval($item["group_id"]),
                "subject_id"        => intval($item["subject_id"]),
                "teacher_uid"       => intval($item["teacher_uid"]),
                "start_time"        => intval($item["start_time"]),
                "end_time"          => intval($item["end_time"]),
                "state"             => intval($item["state"]),
                "area_id"           => intval($item["area_id"]),
                "room_id"           => intval($item["room_id"]),
                "update_time"       => time(),
                "create_time"       => time(), 
            );
            $ret = $this->daoCurriculum->insertRecords($p1);
            if ($ret == false) {
                $this->daoCurriculum->rollback();
                return false;
            }
        }
        if (!empty($profile['delScheduleIds'])) {
            $conds = array(
                sprintf("schedule_id in (%s)", implode(",", $profile['delScheduleIds'])),
                "state" => Service_Data_Schedule::SCHEDULE_ABLE,
            );
            $ret = $this->daoCurriculum->deleteByConds($conds);
            if ($ret == false) {
                $this->daoCurriculum->rollback();
                return false;
            }
        }
        $this->daoCurriculum->commit();
        return true;
    }

    // 根据conds获取列表信息
    public function getListByConds($conds, $field = array(), $indexs = null, $appends = null) {
        $field = empty($field) || !is_array($field)? $this->daoCurriculum->arrFieldsMap : $field;
        $lists = $this->daoCurriculum->getListByConds($conds, $field, $indexs, $appends);
        if (empty($lists)) {
            return array();
        }
        return $lists;
    }

    // 获取total
    public function getTotalByConds($conds) {
        return  $this->daoCurriculum->getCntByConds($conds);
    }

    // 检测订单是否有时间冲突
    public function checkStudentTimes ($needTimes, $needDays, $studentUid) {
        $conds= array(
            sprintf('start_time >= %d', $needDays['sts']),
            sprintf('end_time <= %d', $needDays['ets']),
            'student_uid' => intval($studentUid),
            'state' => Service_Data_Schedule::SCHEDULE_ABLE,
        );
        $list = $this->getListByConds($conds);
        if ($list === false) {
            return false;
        }
        if (empty($list)) {
            return array();
        }

        $diff = array();
        foreach ($list as $item) {
            $flag = false;
            foreach ($needTimes as $t) {
                // 重复的过滤
                if ($t['order_id'] == $item['order_id'] && $t['id'] == $item['schedule_id']) {
                    continue;
                }
                if ($t['sts'] > $item['start_time'] && $t['sts'] < $item['end_time']) {
                    $flag = true;
                    break;
                }
                if ($t['ets'] > $item['start_time'] && $t['ets'] < $item['end_time']) {
                    $flag = true;
                    break;
                }
                if ($t['sts'] < $item['start_time'] && $t['ets'] > $item['end_time']) {
                    $flag = true;
                    break;
                }
                if ($t['sts'] == $item['start_time'] || $t['ets'] == $item['end_time']) {
                    $flag = true;
                    break;
                }
            }
            if ($flag) {
                $diff[] = $item;
            }
        }
        return $diff;
    }

}
