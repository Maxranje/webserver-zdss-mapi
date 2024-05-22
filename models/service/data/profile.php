<?php

class Service_Data_Profile {

    private $daoUser ;

    const USER_TYPE_SUPER   = 9;
    const USER_TYPE_ADMIN   = 11;
    const USER_TYPE_PARTNER = 10;
    const USER_TYPE_STUDENT = 12;
    const USER_TYPE_TEACHER = 13;

    const STUDENT_ABLE      = 1;
    const STUDENT_DISABLE   = 2;

    const ADMIN_GRANT       = [self::USER_TYPE_ADMIN, self::USER_TYPE_SUPER, self::USER_TYPE_TEACHER, self::USER_TYPE_PARTNER];
    const STUDENT_STATE     = [self::STUDENT_ABLE, self::STUDENT_DISABLE];

    const RECHARGE          = 1; 
    const REFUND            = 2;

    public function __construct() {
        $this->daoUser = new Dao_User () ;
    }

    // 根据用户名和密码获取用户信息
    public function getUserInfoByNameAndPass ($username, $passport){
        $arrConds = array(
            'name'  => $username,
            'passport'  => $passport,
        );

        $userinfo = $this->daoUser->getRecordByConds($arrConds, $this->daoUser->arrFieldsMap);
        if (empty($userinfo)) {
            return array();
        }

        $userinfo['create_time'] = date('Y-m-d H:i:s', $userinfo['create_time']);
        $userinfo['update_time'] = date('Y-m-d H:i:s', $userinfo['update_time']);
        return $userinfo;
    }

    // 根据用户名和手机获取用户信息
    public function getUserInfoByNameAndPhone ($username, $phone){
        $arrConds = array(
            'name'  => $username,
            'phone'  => $phone,
        );

        $userinfo = $this->daoUser->getRecordByConds($arrConds, $this->daoUser->arrFieldsMap);
        if (empty($userinfo)) {
            return array();
        }

        $userinfo['create_time'] = date('Y-m-d H:i:s', $userinfo['create_time']);
        $userinfo['update_time'] = date('Y-m-d H:i:s', $userinfo['update_time']);
        return $userinfo;
    }

    public function getUserInfoByName ($name){
        $arrConds = array(
            'name'  => $name,
        );

        $userinfo = $this->daoUser->getRecordByConds($arrConds, $this->daoUser->arrFieldsMap);
        if (empty($userinfo)) {
            return array();
        }
        return $userinfo;
    }

    public function getUserInfoLikeName ($name, $fields = array()){
        $arrConds = array(
            "nickname like '%".$name."%'",
        );

        $fields = empty($fields) ? $this->daoUser->arrFieldsMap : $fields;

        $userinfo = $this->daoUser->getListByConds($arrConds, $fields);
        if (empty($userinfo)) {
            return array();
        }
        return $userinfo;
    }


    public function getStudentInfoByPhone ($phone){
        $arrConds = array(
            'phone'  => $phone,
            'type'   => self::USER_TYPE_STUDENT,
        );

        $userinfo = $this->daoUser->getRecordByConds($arrConds, $this->daoUser->arrFieldsMap);
        if (empty($userinfo)) {
            return array();
        }
        return $userinfo;
    }

    // 根据uid获取用户信息
    public function getUserInfoByUid ($uid){
        $arrConds = array(
            'uid'  => $uid,
        );

        $userinfo = $this->daoUser->getRecordByConds($arrConds, $this->daoUser->arrFieldsMap);
        if (empty($userinfo)) {
            return array();
        }

        $userinfo['create_time'] = date('Y-m-d H:i:s', $userinfo['create_time']);
        return $userinfo;
    }

    // 根据用户uids 获取用户信息
    public function getUserInfoByUids ($uids){
        $arrConds = array(
            sprintf("uid in (%s)", implode(",", $uids)),
        );

        $userinfos = $this->daoUser->getListByConds($arrConds, $this->daoUser->arrFieldsMap);
        if (empty($userinfos)) {
            return array();
        }
        return $userinfos;
    }

    // 修改
    public function editUserInfo ($uid, $profile) {
        if (isset($profile['type']) && $profile['type'] == Service_Data_Profile::USER_TYPE_STUDENT && 
            !empty($profile['sop_uid'])) {
            $this->daoUser->startTransaction();
            $ret = $this->daoUser->updateByConds(array('uid' => $uid), $profile);
            if ($ret == false) {
                $this->daoUser->rollback();
                return false;
            }

            // 更新所有排课信息
            $daoCurrent = new Dao_Curriculum();
            $c1 = array(
                'student_uid' => $uid,
                'state' => Service_Data_Schedule::SCHEDULE_ABLE,
            );
            $p1 = array(
                'sop_uid' => $profile['sop_uid'],
            );
            $ret = $daoCurrent->updateByConds($c1, $p1);
            if ($ret == false) {
                $this->daoUser->rollback();
                return false;
            }
            $this->daoUser->commit();
            return true;
        }
        return $this->daoUser->updateByConds(array('uid' => $uid), $profile);
    }

    // 添加
    public function createUserInfo ($profile) {
        return $this->daoUser->insertRecords($profile);
    }

    // 学生充值
    public function rechargeUser ($userInfo, $balance, $plan, $remark) {
        $this->daoUser->startTransaction();

        $uid = $userInfo['uid'];

        $extra = empty($userInfo['ext']) ? array(): json_decode($userInfo['ext'], true);
        if (!isset($extra['total_balance'])){
            $extra['total_balance'] = 0;    
        }
        $extra['total_balance'] += $balance;

        $rechargeProfile = array(
            'update_time' => time(),
            'ext' => json_encode($extra),
        );
        if (empty($plan)) {
            $rechargeProfile[] = sprintf("balance=balance+%d", $balance);
        }

        $ret = $this->editUserInfo($uid, $rechargeProfile);
        if ($ret == false) {
            $this->daoUser->rollback();
            return false;
        }

        $profile = array(
            "uid"           => $uid, 
            "type"          => self::RECHARGE,
            "operator"      => OPERATOR,
            "capital"       => $balance,
            "plan_id"       => empty($plan['id']) ? 0 : intval($plan['id']),
            "update_time"   => time(),
            "create_time"   => time(),
            "ext"           => json_encode(array(
                'remark'=>$remark, 
                "plan" => empty($plan) ? array() : $plan,
            )),
        );
        $daoCapital = new Dao_Capital();
        $ret = $daoCapital->insertRecords($profile);
        if ($ret == false) {
            $this->daoUser->rollback();
            return false;
        }

        $this->daoUser->commit();
        return true;
    }

    // 学生退款
    public function refundUser ($userInfo, $balance, $remark) {
        $this->daoUser->startTransaction();
        $uid = $userInfo['uid'];

        $extra = empty($userInfo['ext']) ? array(): json_decode($userInfo['ext'], true);
        $extra['total_balance'] -= $balance;

        $rechargeProfile = array(
            sprintf("balance=balance-%d", $balance),
            'update_time' => time(),
            'ext' => json_encode($extra),
        );
        $ret = $this->editUserInfo($uid, $rechargeProfile);
        if ($ret == false) {
            $this->daoUser->rollback();
            return false;
        }

        $profile = array(
            "uid"           => $uid, 
            "type"          => self::REFUND,
            "operator"      => OPERATOR,
            "capital"       => $balance,
            "update_time"   => time(),
            "create_time"   => time(),
            "ext"           => json_encode(array('remark'=>$remark)),
        );
        $daoCapital = new Dao_Capital();
        $ret = $daoCapital->insertRecords($profile);
        if ($ret == false) {
            $this->daoUser->rollback();
            return false;
        }

        $this->daoUser->commit();
        return true;
    }

    // 删除
    public function deleteUserInfo ($uid, $type) {
        // 需要处理删除后的订单情况
        $this->daoUser->startTransaction();
        $conds = array(
            'uid' => $uid,
        );
        $ret = $this->daoUser->deleteByConds($conds);
        if ($ret == false) {
            $this->daoUser->rollback();
            return false;
        }
        
        // 删掉关联的权限
        if ($type == self::USER_TYPE_ADMIN || $type == self::USER_TYPE_TEACHER) {
            $daoRoles = new Dao_Rolesmap();
            $ret = $daoRoles->deleteByConds($conds);
            if ($ret == false) {
                $this->daoUser->rollback();
                return false;
            }
        }

        // 如果是管理员, 会删掉课程和班级中助教
        if ($type == self::USER_TYPE_ADMIN) {
            $daoGroup = new Dao_Group();
            $ret = $daoGroup->updateByConds(array('area_operator' => $uid), array('area_operator' => 0));
            if ($ret == false) {
                $this->daoUser->rollback();
                return false;
            }
            $daoSchedule = new Dao_Schedule();
            $ret = $daoSchedule->updateByConds(array('area_operator' => $uid), array('area_operator' => 0));
            if ($ret == false) {
                $this->daoUser->rollback();
                return false;
            }
        }

        // 如果是教师, 删掉lock
        if ($type == self::USER_TYPE_TEACHER) {
            $daoLock = new Dao_Lock();
            $ret = $daoLock->deleteByConds($conds);
            if ($ret == false) {
                $this->daoUser->rollback();
                return false;
            }
        }

        $this->daoUser->commit();
        return $ret;
    }

    public function setUserSession ($userInfo) {
        return Zy_Core_Session::getInstance()->setSessionUserInfo(
            $userInfo['uid'], 
            $userInfo['name'], 
            $userInfo['passport'],
            $userInfo['phone'], 
            $userInfo['type'],
            $userInfo['pages'],
            $userInfo['modes']);
    }

    public function delUserSession () {
        return Zy_Core_Session::getInstance()->delSessionAndCookie();
    }

    public function getListByConds($conds, $field = array(), $indexs = null, $appends = null) {
        $field = empty($field) || !is_array($field)? $this->daoUser->arrFieldsMap : $field;
        $lists = $this->daoUser->getListByConds($conds, $field, $indexs, $appends);
        if (empty($lists)) {
            return array();
        }
        return $lists;
    }

    public function getTotalByConds($conds) {
        return  $this->daoUser->getCntByConds($conds);
    }
}