<?php

class Service_Data_Schedule {

    const CATEGORY_STUDENT_PAID = 1;
    const CATEGORY_TEACHER_PAID = 2;

    const SCHEDULE_ABLE = 1;
    const SCHEDULE_DONE = 2;

    private $daoSchedule ;

    public function __construct() {
        $this->daoSchedule = new Dao_Schedule () ;
    }

    // 创建订单
    public function create ($params) {
        $this->daoSchedule->startTransaction();
        foreach ($params['needTimes'] as $time) {
            $profile = array(
                "group_id"      => $params['group_id'],
                "column_id"     => $params['column_id'],
                "teacher_uid"   => $params['teacher_uid'],
                "subject_id"    => $params['subject_id'],
                "area_id"       => $params['area_id'],
                "start_time"    => $time['sts'] , 
                "end_time"      => $time['ets'], 
                "area_operator" => $params['area_operator'],
                "operator"      => OPERATOR,
                "state"         => self::SCHEDULE_ABLE, 
                "update_time"   => time(),
                "create_time"   => time(), 
            );
            $ret = $this->daoSchedule->insertRecords($profile);
            if ($ret == false) {
                $this->daoSchedule->rollback();
                return false;
            }
        }
        $this->daoSchedule->commit();
        return true;
    }

    // 更新
    public function update ($params) {
        $this->daoSchedule->startTransaction();
        $conds = array(
            'id' => $params['id'],
        );

        $profile = array(
            "column_id"         => intval($params['column_id']),
            "start_time"        => intval($params['needTimes']['sts']), 
            "end_time"          => intval($params['needTimes']['ets']),
            "area_operator"     => intval($params['area_op']),
	        'teacher_uid'       => intval($params['teacher_uid']),
            'subject_id'        => intval($params['subject_id']),
	        "operator"          => OPERATOR,
            "update_time"       => time(),
        );
        $ret = $this->daoSchedule->updateByConds($conds, $profile);
        if ($ret == false) {
            $this->daoSchedule->rollback();
            return false;
        }

        // 更新订单
        $conds = array(
            'schedule_id' => $params['id'],
        );
        $profile = array(
            "column_id"         => intval($params['column_id']),
            "start_time"        => intval($params['needTimes']['sts']), 
            "end_time"          => intval($params['needTimes']['ets']),
	        'teacher_uid'       => intval($params['teacher_uid']),
            'subject_id'        => intval($params['subject_id']),
            "update_time"       => time(),
        );
        $daoCurriculum = new Dao_Curriculum();
        $ret = $daoCurriculum->updateByConds($conds, $profile);
        if ($ret == false) {
            $this->daoSchedule->rollback();
            return false;
        }
        $this->daoSchedule->commit();
    }

    public function updateArea ($arrParams) {
        $daoCurriculum = new Dao_Curriculum();
        $this->daoSchedule->startTransaction();
        foreach ($arrParams as $params) {
            $conds = array(
                'id' => intval($params['id']),
            );
    
            $profile = array(
                "room_id"   => intval($params['room_id']),
                "area_id"   => intval($params['area_id']),
                "ext"       => $params['ext'],
            );
            $ret = $this->daoSchedule->updateByConds($conds, $profile);
            if ($ret == false) {
                $this->daoSchedule->rollback();
                return false;
            }
    
            // 更新用户关联
            $conds = array(
                'schedule_id' => intval($params['id']),
            );
            $ret = $daoCurriculum->updateByConds($conds, $profile);
            if ($ret == false) {
                $this->daoSchedule->rollback();
                return false;
            }
        }

        $this->daoSchedule->commit();
        return true;
    }

    // 获取一个单个
    public function getScheduleById ($id){
        $arrConds = array(
            'id'  => intval($id),
        );

        $arrFields = $this->daoSchedule->arrFieldsMap;

        $data = $this->daoSchedule->getRecordByConds($arrConds, $arrFields);
        if (empty($data)) {
            return array();
        }

        return $data;
    }

    // 根据ids 获取排课
    public function getScheduleByIds ($ids){
        $arrConds = array(
            sprintf("id in (%s)", implode(",", $ids)),
        );

        $arrFields = $this->daoSchedule->arrFieldsMap;

        $data = $this->daoSchedule->getListByConds($arrConds, $arrFields);
        if (empty($data)) {
            return array();
        }

        return $data;
    }

    // 删除所有排课
    public function deleteSchedules ($ids) {
        $this->daoSchedule->startTransaction();

        // 删除学生关联
        $daoCurriculum = new Dao_Curriculum();
        $conds = array(
            sprintf("schedule_id in (%s)", $ids),
        );
        $ret = $daoCurriculum->deleteByConds($conds);
        if ($ret == false) {
            $this->daoSchedule->rollback();
            return false;
        }

        // 删除排课
        $conds = array(
            sprintf("id in (%s)", $ids),
        );
        $ret = $this->daoSchedule->deleteByConds($conds);
        if ($ret == false) {
            $this->daoSchedule->rollback();
            return false;
        }
        $this->daoSchedule->commit();
        return $ret;
    }


    public function getLastDuration($groupIds) {
        $conds = array(
            sprintf("group_id in (%s)", implode(",", $groupIds)),
            "state = 0",
        );
        $fields = array(
            "group_id",
            "start_time",
            "end_time",
        );
        $lists = $this->daoSchedule->getListByConds($conds, $fields, null, null);
        if (empty($lists)) {
            return array();
        }

        $result = array();
        foreach ($lists as $item) {
            if (empty($result[$item['group_id']])) {
                $result[$item['group_id']] = 0;
            }
            $timeLength = ($item['end_time'] - $item['start_time']) / 3600;
            $result[$item['group_id']] += $timeLength;
        }
        return $result;
    }

    public function getTotalByConds($conds) {
        return  $this->daoSchedule->getCntByConds($conds);
    }

    // 结算
    public function checkout ($params) {
        $now = time();
        $daoRecords = new Dao_Records();
        $daoOrder = new Dao_Order();
        $daoCurriculum = new Dao_Curriculum();

        
        $schedule         = $params['schedule'];
        $column           = $params['column'];
        $subject          = $params['subjectInfo'];
        $orderInfos       = $params['orderInfos'];
        $studentUids      = $params['studentUids'];

        // 按小时计算
        $timeLength     = ($schedule['end_time'] - $schedule['start_time']) / 3600;

        // 获取教师的price
        $teacherPrice   = 0;
        if (!empty($column['price'])){
            $column['price'] = json_decode($column['price'], true);
            foreach ($column['price'] as $item) {
                if (count($studentUids) > $item['number']) {
                    continue;
                }
                $teacherPrice = intval($item['price'] * $timeLength);
                break;
            }
        }

        $this->daoSchedule->startTransaction();
        // 插入消费记录
        // 教师收入记录
        $recordsProfile = array(
            "uid"               => intval($schedule['teacher_uid']),
            "type"              => Service_Data_Profile::USER_TYPE_TEACHER,
            "state"             => Service_Data_Records::RECORDS_NOMARL,
            "group_id"          => intval($schedule['group_id']),
            "order_id"          => 0, // 教师不做记录
            "subject_id"        => intval($schedule['subject_id']),
            "teacher_uid"       => 0, // 教师不做记录
            "schedule_id"       => intval($schedule['id']),
            "category"          => self::CATEGORY_TEACHER_PAID,
            "operator"          => OPERATOR,
            "money"             => $teacherPrice,
            'update_time'       => $now,
            'create_time'       => $now,
            'ext'               => json_encode(array(
                "column"    => $column,
                "subject"   => $subject,
            ))
        );
        $ret = $daoRecords->insertRecords($recordsProfile);
        if ($ret == false) {
            $this->daoSchedule->rollback();
            return false;
        }

        // 学生消费记录 和 删除订单中钱
        foreach ($orderInfos as $order) {
            $price = intval($order['price']) * $timeLength;

            $recordsProfile = array(
                "uid"               => intval($order['student_uid']),
                "type"              => Service_Data_Profile::USER_TYPE_STUDENT,
                "state"             => Service_Data_Records::RECORDS_NOMARL,
                "group_id"          => intval($schedule['group_id']),
                "order_id"          => intval($order['order_id']), 
                "subject_id"        => intval($schedule['subject_id']),
                "teacher_uid"       => intval($schedule['teacher_uid']), 
                "schedule_id"       => intval($schedule['id']),
                "category"          => self::CATEGORY_STUDENT_PAID, 
                "operator"          => OPERATOR,
                "money"             => $price,
                'update_time'       => $now,
                'create_time'       => $now,
                'ext'               => json_encode(array(
                    "order"     => $order,
                    "subject"   => $subject,
                ))
            );
            $ret = $daoRecords->insertRecords($recordsProfile);
            if ($ret == false) {
                $this->daoSchedule->rollback();
                return false;
            }

            // 学生订单余额删掉
            $conds = array(
                "order_id" => intval($order['order_id']),
            );
            $orderProfile = array(
                sprintf("balance=balance-%d", $price),
                'update_time' => time(),
            );
            $ret = $daoOrder->updateByConds($conds, $orderProfile);
            if ($ret == false) {
                $this->daoSchedule->rollback();
                return false;
            }
        }

        // 更新排课状态
        $conds = array(
            'id' => intval($schedule['id']),
        );
        $profile = array(
            'state' => self::SCHEDULE_DONE,
            "update_time" => time(),
        );
        $ret = $this->daoSchedule->updateByConds($conds, $profile);
        if ($ret == false) {
            $this->daoSchedule->rollback();
            return false;
        }

        // 更新学生关联排课状态
        $conds = array(
            'schedule_id' => intval($schedule['id']),
        );
        $profile = array(
            'state' => self::SCHEDULE_DONE,
            "update_time" => time(),
        );
        $ret = $daoCurriculum->updateByConds($conds, $profile);
        if ($ret == false) {
            $this->daoSchedule->rollback();
            return false;
        }
        // 提交
        $this->daoSchedule->commit();
        return true;
    }

    // 撤销
    public function revoke($params) {
        $daoRecords = new Dao_Records();
        $daoOrder = new Dao_Order();
        $daoCurriculum = new Dao_Curriculum();

        
        $schedule       = $params['schedule'];
        $orderIds       = $params['orderids'];
        $records        = $params['records'];
        $orderRecords   = array_column($records, null, 'order_id');

        $this->daoSchedule->startTransaction();

        // 加回学生的钱
        foreach ($orderIds as $orderId) {
            if (empty($orderRecords[$orderId]['money'])) {
                continue;
            }
            $conds = array(
                'order_id' => intval($orderId),
            );
            $profile = array(
                sprintf("balance=balance+%d", intval($orderRecords[$orderId]['money']))
            );
            $ret = $daoOrder->updateByConds($conds, $profile);
            if ($ret == false) {
                $this->daoSchedule->rollback();
                return false;
            }
        }

        // 更新排课状态
        $conds = array(
            'id' => intval($schedule['id']),
        );
        $profile = array(
            'state' => self::SCHEDULE_ABLE,
        );
        $ret = $this->daoSchedule->updateByConds($conds, $profile);
        if ($ret == false) {
            $this->daoSchedule->rollback();
            return false;
        }

        // 更新学生关联排课状态
        $conds = array(
            'schedule_id' => intval($schedule['id']),
        );
        $profile = array(
            'state' => self::SCHEDULE_ABLE,
        );
        $ret = $daoCurriculum->updateByConds($conds, $profile);
        if ($ret == false) {
            $this->daoSchedule->rollback();
            return false;
        }

        // 所有记录, 修改为状态
        $conds = array(
            'schedule_id' => intval($schedule['id']),
            'state' => Service_Data_Records::RECORDS_NOMARL,
        );
        $profile = array(
            'state' => Service_Data_Records::RECORDS_REVOKE,
        );
        $ret = $daoRecords->updateByConds($conds, $profile);
        if ($ret == false) {
            $this->daoSchedule->rollback();
            return false;
        }

        // 提交
        $this->daoSchedule->commit();
        return true;
    }

    // 根据group 获取排课数量
    public function getSchduleCountByGroup($ids) {
        $conds = array(
            sprintf("group_id in (%s)", implode(",", $ids))
        );
        $field = array(
            "count(id) as count",
            "group_id",
        );
        $appends = array(
            "group by group_id"
        );
        $lists = $this->daoSchedule->getListByConds($conds, $field, null, $appends);
        if (empty($lists)) {
            return array();
        }
        return $lists;
    }    

    // 根据请求条件获取课表数据
    public function getListByConds($conds, $fields = array(), $indexs = null, $appends = null) {
        $fields = empty($fields) ? $this->daoSchedule->arrFieldsMap : $fields;
        $lists = $this->daoSchedule->getListByConds($conds, $fields, $indexs, $appends);
        if (empty($lists)) {
            return array();
        }
        return $lists;
    }

    // 检测班级时间是否冲突
    public function checkGroup ($needTimes, $needDays, $groupId, $info = array()) {
        $conds= array(
            sprintf('start_time >= %d', $needDays['sts']),
            sprintf('end_time <= %d', $needDays['ets']),
            'group_id' => intval($groupId),
            'state' => self::SCHEDULE_ABLE,
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
            if (!empty($info) && $item['id'] == $info['id']) {
                continue;
            }
            $flag = false;
            foreach ($needTimes as $t) {
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

    // 检测教师时间是否有冲突
    public function checkTeacherPk ($needTimes, $needDays, $teacherUid, $info = array()) {
        $conds= array(
            sprintf('start_time >= %d', $needDays['sts']),
            sprintf('end_time <= %d', $needDays['ets']),
            'teacher_uid' => intval($teacherUid),
            'state' => self::SCHEDULE_ABLE,
        );
        $list = $this->getListByConds($conds);
        if ($list === false) {
            return false;
        }

        // 锁定的时间
        $conds= array(
            sprintf('start_time >= %d', $needDays['sts']),
            sprintf('end_time <= %d', $needDays['ets']),
            'uid' => intval($teacherUid),
        );
        $serviceLock = new Service_Data_Lock();
        $locks = $serviceLock->getListByConds($conds);
        if ($locks === false) {
            return false;
        }

        if (empty($list) && empty($locks)) {
            return array();
        }

        // 2个记录合并
        $diff = array(
            "lock" => array(),
            'schedule' => array(),
        );
        $list = array_merge($list, $locks);
        foreach ($list as $item) {
            if (!empty($info) && $item['id'] == $info['id']) {
                continue;
            }
            $flag = false;
            foreach ($needTimes as $t) {
                if ($t['sts'] > $item['start_time'] && $t['sts'] < $item['end_time']) {
                    $flag = true;
                }
                if ($t['ets'] > $item['start_time'] && $t['ets'] < $item['end_time']) {
                    $flag = true;
                }
                if ($t['sts'] < $item['start_time'] && $t['ets'] > $item['end_time']) {
                    $flag = true;
                }
                if ($t['sts'] == $item['start_time'] || $t['ets'] == $item['end_time']) {
                    $flag = true;
                }
            }
            if ($flag) {
                if (!empty($item['column_id'])) {
                    $diff['schedule'][] = $item;
                } else {
                    $diff['lock'][] = $item;
                }
            }
        }
        return $diff;
    }

    public function checkRoom ($needTimes, $needDays, $info) {
        $conds= array(
            sprintf('start_time >= %d', $needDays['sts']),
            sprintf('end_time <= %d', $needDays['ets']),
            "room_id = " . intval($info['room_id']) ,
            "area_id = " . intval($info['area_id']) ,
            'state' => self::SCHEDULE_ABLE,
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
            if (!empty($info) && $item['id'] == $info['id']) {
                continue;
            }
            $flag = false;
            foreach ($needTimes as $t) {
                if ($t['sts'] > $item['start_time'] && $t['sts'] < $item['end_time']) {
                    $flag = true;
                }
                if ($t['ets'] > $item['start_time'] && $t['ets'] < $item['end_time']) {
                    $flag = true;
                }
                if ($t['sts'] < $item['start_time'] && $t['ets'] > $item['end_time']) {
                    $flag = true;
                }
                if ($t['sts'] == $item['start_time'] || $t['ets'] == $item['end_time']) {
                    $flag = true;
                }
            }
            if ($flag) {
                $diff[] = $item;
            }
        }
        return $diff;
    }

    // check param 中的times是否合法
    public function checkParamsTime ($needTimes) {
        $times = $needTimes;
        foreach ($times as $k1 => $item) {
            foreach ($needTimes as $k2 => $t) {
                // 比较, 开始时间大于存开始时间,  结束时间小于存结束时间
                if ($k1 == $k2) {
                    continue;
                }
                if ($t['sts'] > $item['sts'] && $t['sts'] < $item['ets']) {
                    return false;
                }
                if ($t['ets'] > $item['sts'] && $t['ets'] < $item['ets']) {
                    return false;
                }
                if ($t['sts'] < $item['sts'] && $t['ets'] > $item['ets']) {
                    return false;
                }
                if ($t['sts'] == $item['sts'] || $t['ets'] == $item['ets']) {
                    return false;
                }
            }
        }
        return true;
    }

    // check 2个时间范围数据的时间是否有冲突
    public function checkDefaultTimes ($list1, $list2) {
        if (empty($list1) || empty($list2)) {
            return array();
        }

        $diff = array();
        foreach ($list1 as $item) {
            $flag = false;
            foreach ($list2 as $t) {
                if ($t['sts'] > $item['sts'] && $t['sts'] < $item['ets']) {
                    $flag = true;
                }
                if ($t['ets'] > $item['sts'] && $t['ets'] < $item['ets']) {
                    $flag = true;
                }
                if ($t['sts'] < $item['sts'] && $t['ets'] > $item['ets']) {
                    $flag = true;
                }
                if ($t['sts'] == $item['sts'] || $t['ets'] == $item['ets']) {
                    $flag = true;
                }
            }
            if ($flag) {
                $diff[] = $item;
            }
        }

        return $diff;
    }

    public function checkRandAreaRoom ($needDays, $scheduleLists, $areaLists) {
        $result = array();
        foreach ($needDays as $key => $item) {
            if (empty($scheduleLists[$key])) {
                continue;
            }
            $conds = array(
                sprintf("start_time >= %d", $item['sts']),
                sprintf("end_time <= %d", $item['ets']),
                "room_id > 0",
                "state" => Service_Data_Schedule::SCHEDULE_ABLE,
            );
            $filed = array(
                "start_time as sts",
                'end_time as ets',
                "room_id",
                "area_id",
                "id",
            );
            $lists = $this->getListByConds($conds, $filed);

            foreach ($scheduleLists[$key] as $v) {
                $diff = $this->checkDefaultTimes($lists, array($v));
                if (empty($diff)) {
                    $result[$v['id']] = $areaLists[$v['area_id']]['rooms'][0];
                    continue;
                }   
                $realDiff = array();
                foreach ($diff as $vv) {
                    if ($areaLists[$v['area_id']]['is_online'] == Service_Data_Area::ONLINE) {
                        continue;
                    }
                    if ($vv['area_id'] != $v['area_id']) {
                        continue;
                    }
                    $realDiff[] = $vv['room_id'];
                }
                if (empty($realDiff)) {
                    $result[$v['id']] = $areaLists[$v['area_id']]['rooms'][0];
                    continue;
                }

                $rooms = array_column($areaLists[$v['area_id']]['rooms'], null , "id");
                $rids = Zy_Helper_Utils::arrayInt($rooms, "id");
                $diff2 = array_values(array_diff($rids, $realDiff));

                if (!empty($diff2) && !empty($rooms[$diff2[0]])) {
                    $result[$v['id']] = $rooms[$diff2[0]];
                }
            }
        }

        return $result;
    }
}
