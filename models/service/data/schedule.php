<?php

class Service_Data_Schedule {

    const CATEGORY_STUDENT_RECHARGE = 1;
    const CATEGORY_TEACHER_RECHARGE = 2;
    const CATEGORY_STUDENT_PAID = 3;
    const CATEGORY_TEACHER_PAID = 4;
    const CATEGORY_STUDENT_PAID_PERSONAL = 5;
    const CATEGORY_TEACHER_MUILT_PAID = 6;

    private $daoSchedule ;

    public function __construct() {
        $this->daoSchedule = new Dao_Schedule () ;
    }

    public function create ($params) {
        $this->daoSchedule->startTransaction();
        foreach ($params['needTimes'] as $time) {
            $profile = array(
                "group_id"  => $params['group_id'],
                "column_id"  => $params['column_id'],
                "teacher_id"  => $params['teacher_id'],
                "area_id"  => $params['area_id'],
                "start_time"  => $time['sts'] , 
                "area_op" => $params['area_op'],
                "end_time"  => $time['ets'], 
                "operator" => OPERATOR,
                "state"  => $params['state'] , 
                "update_time"  => time(),
                "create_time"  => time(), 
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

    public function update ($params) {
        $conds = array(
            'id' => $params['id'],
        );

        $profile = array(
            "column_id"  => $params['column_id'],
            "start_time"  => $params['needTimes']['sts'] , 
            "end_time"  => $params['needTimes']['ets'],
            "area_op" => $params['area_op'],
	        'teacher_id' => $params['teacher_id'],
	        "operator" => OPERATOR,
            "state"  => 1 , 
            "update_time"  => time(),
        );
        return $this->daoSchedule->updateByConds($conds, $profile);
    }

    public function updateArea ($params) {
        $conds = array(
            'id' => $params['id'],
        );

        $profile = array(
            "room_id" => $params['room_id'],
            "area_id" => $params['area_id'],
            "ext" => $params['ext'],
        );
        return $this->daoSchedule->updateByConds($conds, $profile);
    }

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

    public function getScheduleByIds ($ids){
        $arrConds = array(
            sprintf("id in (%s)", $ids),
        );

        $arrFields = $this->daoSchedule->arrFieldsMap;

        $data = $this->daoSchedule->getListByConds($arrConds, $arrFields);
        if (empty($data)) {
            return array();
        }

        return $data;
    }

    public function deleteSchedule ($id) {
        $conds = array(
            'id' => $id,
        );
        $ret = $this->daoSchedule->deleteByConds($conds);
        return $ret;
    }

    public function deleteSchedules ($ids) {
        $conds = array(
            sprintf("id in (%s)", $ids),
        );
        $ret = $this->daoSchedule->deleteByConds($conds);
        return $ret;
    }

    public function getListByConds($conds, $fields = array(), $indexs = null, $appends = null) {
        if (empty($fields)) {
            $fields = $this->daoSchedule->arrFieldsMap;
        }
        $lists = $this->daoSchedule->getListByConds($conds, $fields, $indexs, $appends);
        if (empty($lists)) {
            return array();
        }
        return $lists;
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

    public function checkJobs ($params) {
        $now = time();
        $daoUser = new Dao_User();
        $daoCapital = new Dao_Capital();

        // 按小时计算
        $timeLength = ($params['job']['end_time'] - $params['job']['start_time']) / 3600;
        $teacherPrice = intval($params['column']['price'] * $timeLength);
        $teacherCategory = self::CATEGORY_TEACHER_PAID;
        $studentPrice = intval($params['group']['price'] * $timeLength);

        // 过滤有效的uid
        foreach ($params['studentUids'] as $key => $uid) {
            if (empty($params['studentInfos'][$uid])) {
                unset($params['studentUids'][$key]);
            }
        }
        $params['studentUids'] = array_values($params['studentUids']);
        unset($params['studentInfos']);

        // 根据用户数量判断是否走重新算价
        if (!empty($params['column']['number']) && count($params['studentUids']) >= $params['column']['number']){
            $teacherPrice = intval($params['column']['muilt_price'] * $timeLength);
            $teacherCategory = self::CATEGORY_TEACHER_MUILT_PAID;
        }

        // 获取班级单个学生价格
        $singlePrice = array();
        if (!empty($params['group']['student_price'])) {
            $singlePrice = json_decode($params['group']['student_price'], true);
        }

        $this->daoSchedule->startTransaction();
        // 教师
        $profile = array(
            'uid' => intval($params['column']['teacher_id']),
            'type' => Service_Data_User_Profile::USER_TYPE_TEACHER,
            'column_id' => $params['job']['column_id'],
            'group_id' => $params['job']['group_id'],
            'schedule_id' => $params['job']['id'],
            'category' => $teacherCategory,  // 1用户充值, 2作者充值, 3,用户消耗, 4,作者收入
            'operator' => OPERATOR,
            'capital' => $teacherPrice,
            'update_time' => $now,
            'create_time' => $now,
            'ext' => json_encode($params)
        ); 
        $ret = $daoCapital->insertRecords($profile);
        if ($ret == false) {
            $this->daoSchedule->rollback();
            return false;
        }

        // 教师收入
        $conds = array(
            'uid' => intval($params['column']['teacher_id'])
        );
        $profile = array(
            "teacher_capital = teacher_capital+" . $teacherPrice ,
        ); 
        $ret = $daoUser->updateByConds($conds, $profile);
        if ($ret == false) {
            $this->daoSchedule->rollback();
            return false;
        }

        // 获取班级中学生独立价格
        foreach ($params['studentUids'] as $uid) {
            $stuPrice = $studentPrice; 
            $stuCategory =  self::CATEGORY_STUDENT_PAID;
            if (isset($singlePrice[$uid])) {
                $stuPrice = intval(intval($singlePrice[$uid]) * $timeLength);
                $stuCategory = self::CATEGORY_STUDENT_PAID_PERSONAL;
            }
            // 学生支出
            $profile = array(
                'uid' => intval($uid),
                'type' => Service_Data_User_Profile::USER_TYPE_STUDENT,
                'category' => $stuCategory,  // 1用户充值, 2作者充值, 3,用户消耗(班级), 4,作者收入, 5 用户消耗(个人)
                'column_id' => $params['job']['column_id'],
                'group_id' => $params['job']['group_id'],
                'schedule_id' => $params['job']['id'],
                'operator' => OPERATOR,
                'capital' => $stuPrice,
                'update_time' => $now,
                'create_time' => $now,
                'ext' => json_encode($params)
            ); 
            $ret = $daoCapital->insertRecords($profile);
            if ($ret == false) {
                $this->daoSchedule->rollback();
                return false;
            }

            // 学生消耗
            $conds = array(
                'uid' => intval($uid),
            );
            $profile = array(
                "student_capital = student_capital - " . $stuPrice ,
            ); 
            $ret = $daoUser->updateByConds($conds, $profile);
            if ($ret == false) {
                $this->daoSchedule->rollback();
                return false;
            }
        }

        // 更新订单状态
        $conds = array(
            'id' => $params['job']['id'],
        );
        $profile = array(
            'state' => 0,
        );
        $ret = $this->daoSchedule->updateByConds($conds, $profile);
        if ($ret == false) {
            $this->daoSchedule->rollback();
            return false;
        }
        $this->daoSchedule->commit();

        return true;
    }

    public function revoke($params) {
        $this->daoSchedule->startTransaction();
        $daoCapital = new Dao_Capital();
        $daoUser = new Dao_User();
        $conds = array(
            'uid' => 0,
        );
        foreach ($params['list'] as $item) {
            $conds['uid'] = intval($item['uid']);
            if ($item['type'] == Service_Data_User_Profile::USER_TYPE_STUDENT) {
                $ret= $daoUser->updateByConds($conds, "student_capital=student_capital+".$item['capital']);
            } else {
                $ret= $daoUser->updateByConds($conds, "teacher_capital=teacher_capital-".$item['capital']);
            }

            if ($ret == false ) {
                $this->daoSchedule->rollback();
                return false;
            }
        }
        $ret = $daoCapital->deleteByConds(array('schedule_id' => $params['id']));
        if ($ret == false) {
            $this->daoSchedule->rollback();
            return false;
        }
        $ret = $this->daoSchedule->updateByConds(array('id' => $params['id']), array('state' => 1));
        if ($ret == false) {
            $this->daoSchedule->rollback();
            return false;
        }
        $this->daoSchedule->commit();
        return true;
    }

    public function checkGroup ($needTimes, $needDays, $groupId, $info = array()) {
        $conds= array(
            sprintf('start_time >= %d', $needDays['sts']),
            sprintf('end_time <= %d', $needDays['ets']),
            'group_id' => intval($groupId),
            'state' => 1,
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

    public function checkTeacherPk ($needTimes, $needDays, $teacherId, $info = array()) {
        $conds= array(
            sprintf('start_time >= %d', $needDays['sts']),
            sprintf('end_time <= %d', $needDays['ets']),
            'teacher_id' => intval($teacherId),
            'state' => 1,
        );
        $list = $this->getListByConds($conds);
        if ($list === false) {
            return false;
        }

        // 锁定的时间
        $conds= array(
            sprintf('start_time >= %d', $needDays['sts']),
            sprintf('end_time <= %d', $needDays['ets']),
            'uid' => intval($teacherId),
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
            'state' => 1,
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
}
