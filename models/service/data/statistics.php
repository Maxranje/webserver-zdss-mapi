<?php

class Service_Data_Statistics {

    private $daoCapital ;

    public function __construct() {
        $this->daoCapital = new Dao_Capital () ;
    }

    public function getListByConds($conds, $field = array(), $indexs = null, $appends = null) {
        $field = empty($field) || !is_array($field) ? $this->daoCapital->arrFieldsMap : $field;
        $lists = $this->daoCapital->getListByConds($conds, $field, $indexs, $appends);
        if (empty($lists)) {
            return array();
        }
        return $lists;
    }

    public function getTotalByConds($conds) {
        return  $this->daoCapital->getCntByConds($conds);
    }

    public function getListByJobId($jobId) {
        $conds = array(
            'schedule_id' => $jobId,
        );
        $field = empty($field) || !is_array($field) ? $this->daoCapital->arrFieldsMap : $field;
        $lists = $this->daoCapital->getListByConds($conds, $field);
        if (empty($lists)) {
            return array();
        }
        return $lists;
    }


    public function edit ($id, $profile) {
        $arrConds = array(
            'id'  => $id,
        );

        $ret = $this->daoCapital->updateByConds($arrConds, $profile);
        return $ret;
    }

    public function getDetailsList ($studentName, $groupName, $pn, $rn) {
        $sql = "select 
                u.uid, u.nickname, u.type, u.birthplace, u.student_capital,
                m.group_id, g.name, g.price, g.duration, g.area_op, g.student_price
            from 
                tblUser u left join tblGroupMap m
            on 
                u.uid = m.student_id
            left join tblGroup g
            on 
                g.id = m.group_id 
            where u.type=12 and m.group_id > 0";

        $where = "";
        if (!empty($studentName)) {
            $where = " and u.nickname like '%" . $studentName . "%'";
        }
        if (!empty($groupName)) {
            $where = " and g.name like '%" . $groupName . "%'";
        }
        if (!empty($where)) {
            $sql .= $where;
        }

        if ($rn > 0) {
            $sql .= " limit " . $pn . ", " . $rn;
        }

        $dao = new Dao_Capital();
        $lists = $dao->query($sql);
        if (empty($lists)) {
            return array();
        }
        
        $uids = array();
        $groupIds = array();
        foreach ($lists as $item) {
            $uids[$item['uid']] = intval($item['uid']);
            $groupIds[$item['group_id']] = intval($item['group_id']);
            $uids[$item['area_op']] = intval($item['area_op']);
        }
        $uids = array_values($uids);
        $groupIds = array_values($groupIds);

        $serviceSchedule = new Service_Data_Schedule();
        $scheduleCount = $serviceSchedule->getLastDuration($groupIds);

        # 用户信息
        $conds = array(
            sprintf("uid in (%s)", implode(",", $uids))
        );
        $daoUser = new Dao_User();
        $userInfos = $daoUser->getListByConds($conds, array("uid", "nickname"));
        $userInfos = array_column($userInfos, null, "uid");
        
        # 
        $capital = $dao->getListByConds($conds, array("uid", "group_id", "category", "capital", "ext"));
        $capitalInfos = array();
        foreach ($capital as $item) {
            if (!isset($capitalInfos[$item['uid']])) {
                $capitalInfos[$item['uid']] = array();
            }
            if (!isset($capitalInfos[$item['uid']][$item['group_id']])) {
                $capitalInfos[$item['uid']][$item['group_id']] = array();
            }
            $capitalInfos[$item['uid']][$item['group_id']][] = $item;
        }

        $result = array();
        foreach ($lists as $item) {
            $tmp = array();
            if (isset($scheduleCount[$item['group_id']])) {
                $lastDuration = $item['duration'] - $scheduleCount[$item['group_id']];
            } else {
                $lastDuration = $item['duration'];
            }
            if ($lastDuration <= 0) {
                $progress = 0 ;
            } else {
                $progress = intval(($lastDuration/ $item['duration']) * 100);
            }
            
            // 单价
            $studentPrice = empty($item['student_price']) ? array() : json_decode($item['student_price'], true);

            $tmp['duration_scale'] = sprintf("%s/%s 小时", $lastDuration, $item['duration']);
            $tmp['student_name'] = $item['nickname'];
            $tmp['group_name'] = $item['name'];
            $tmp['group_id'] = $item['group_id'];
            $tmp['uid'] = $item['uid'];
            $tmp['birthplace'] = $item['birthplace'];
            $tmp['group_price'] = (intval($item['price']) / 100) . "元" ;
            $tmp['student_price'] = (intval($item['price']) / 100) . "元" ;
            if (isset($studentPrice[$tmp['uid']])) {
                $tmp['student_price'] = (intval($studentPrice[$tmp['uid']]) / 100) . "元" ;
            }
            $tmp['capital'] = (intval($item['student_capital']) / 100) . "元" ;
            $tmp['duration_progress'] = $progress;
            $tmp['area_op'] = "";
            if (!empty($userInfos[$item['area_op']]['nickname'])) {
                $tmp['area_op'] = $userInfos[$item['area_op']]['nickname'];
            }

            $tmp['expenses'] = 0;
            $realDuration = 0;
            if (!empty($capitalInfos[$item['uid']][$item['group_id']])) {
                foreach ($capitalInfos[$item['uid']][$item['group_id']] as $info) {
                    if (in_array($info['category'], array(3,5))) {
                        $tmp['expenses'] += $info['capital'];
                        $capitalExt = empty($info['ext']) ? array() : json_decode($info['ext'], true);
                        if (!empty($capitalExt['job']['start_time'])  && !empty($capitalExt['job']['end_time'])) {
                            $realDuration += ($capitalExt['job']['end_time'] - $capitalExt['job']['start_time']) / 3600;
                        }
                    }
                }
            }
            
            $tmp['duration_real'] = "-";
            if ($realDuration > 0) {
                $tmp['duration_real'] = $realDuration . "小时";
            }
            $tmp['expenses'] = $tmp['expenses'] / 100;
            $tmp['expenses_info'] = $tmp['expenses'] . "元" ;

            $result[] = $tmp;
        }
        return $result;
    }


    public function getDetailsTotal ($studentName, $groupName) {
        $sql = "select 
                count(*) as count
            from 
                tblUser u left join tblGroupMap m
            on 
                u.uid = m.student_id
            left join tblGroup g
            on 
                g.id = m.group_id 
            where u.type=12 and m.group_id > 0";

        $where = "";
        if (!empty($studentName)) {
            $where = " and u.nickname like '%" . $studentName . "%'";
        }
        if (!empty($groupName)) {
            $where = " and g.name like '%" . $groupName . "%'";
        }
        if (!empty($where)) {
            $sql .= $where;
        }
        
        $dao = new Dao_Capital();
        $data = $dao->query($sql);
        return  empty($data[0]['count']) ? 0 : intval($data[0]['count']);
    }

    public function rechargeCapital ($params) {
        $daoUser = new Dao_User();
        $daoGroup = new Dao_Group();
        $daoCapital = new Dao_Capital();

        // 入参参数
        $userInfo = $params['user_info'];
        $groupInfo = $params['group_info'];
        $pkList = $params['pkList'];
        $newExpenses = intval($params['newExpenses']);
        $oldExpenses = intval($params['oldExpenses']);
        $checkTimeLen = $params['checkTimeLen'];

        // 开启事物
        $daoUser->startTransaction();
        // 更新用户存额
        $conds = array(
            'uid' => intval($userInfo['uid'])
        );
        $profile = array(
            "student_capital" => intval($userInfo['student_capital']) + ($oldExpenses - $newExpenses),
        ); 
        $ret = $daoUser->updateByConds($conds, $profile);
        if ($ret == false) {
            $daoUser->rollback();
            return false;
        }

        // 修改班级客单价
        $studentPrice = empty($groupInfo['student_price']) ? array() : json_decode($groupInfo['student_price'], true);
        $singlePrice = intval($newExpenses / $params['checkTimeLen']);
        $studentPrice[intval($userInfo['uid'])] = $singlePrice;
        $conds = array(
            'id' => intval($groupInfo['id'])
        );
        $profile = array(
            "student_price" => json_encode($studentPrice),
        ); 
        $ret = $daoGroup->updateByConds($conds, $profile);
        if ($ret == false) {
            $daoUser->rollback();
            return false;
        }

        // 更新所有已消费的数据
        foreach ($pkList as $item) {
            $conds = array(
                'id' => intval($item['id'])
            );
            $profile = array(
                "capital" => $singlePrice * $item['timeLen'],
                "update_time" => time(),
            ); 
            $ret = $daoCapital->updateByConds($conds, $profile);
            if ($ret == false) {
                $daoUser->rollback();
                return false;
            }
        }

        $daoUser->commit();
        return true;
    }
}