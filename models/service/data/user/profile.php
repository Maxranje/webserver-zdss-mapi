<?php

class Service_Data_User_Profile {

    private $daoUser ;

    const USER_TYPE_SUPER = 9;
    const USER_TYPE_ADMIN = 11;
    const USER_TYPE_STUDENT = 12;
    const USER_TYPE_TEACHER = 13;

    const ADMIN_GRANT = [self::USER_TYPE_ADMIN, self::USER_TYPE_SUPER, self::USER_TYPE_TEACHER];

    public function __construct() {
        $this->daoUser = new Dao_User () ;
    }

    public function getUserInfo ($username, $passport){
        $arrConds = array(
            'name'  => $username,
            'phone'  => $passport,
        );

        $arrFields = $this->daoUser->arrFieldsMap;

        $userinfo = $this->daoUser->getRecordByConds($arrConds, $arrFields);
        if (empty($userinfo)) {
            return array();
        }

        $userinfo['create_time'] = date('Y-m-d H:i:s', $userinfo['create_time']);
        $userinfo['update_time'] = date('Y-m-d H:i:s', $userinfo['update_time']);
        return $userinfo;
    }

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

    public function editUserInfo ($uid, $profile, $needStudentCapital = false, $needTeacherCapital = false) {
        $arrConds = array(
            'uid' => $uid,
        );

        $daoCapital = null;
        $capital = 0;
        if ($needStudentCapital || $needTeacherCapital) {
            $this->daoUser->startTransaction();
            $daoCapital = new Dao_Capital();
            $capital = $profile['capital'];
            unset($profile['capital']);
        }

        $capital_remark = empty($profile['capital_remark']) ? "" : $profile['capital_remark'];
        unset($profile['capital_remark']);
        
        $ret = $this->daoUser->updateByConds($arrConds, $profile);
        if ($needStudentCapital) {
            if ($ret == false) {
                $this->daoUser->rollback();
            } else {
                $profiles = array(
                    'uid' => intval($uid),
                    'operator' => OPERATOR,
                    'type' => $profile['type'],
                    'category' => Service_Data_Schedule::CATEGORY_STUDENT_RECHARGE,
                    'capital' => intval($capital),
                    'capital_remark' => $capital_remark,
                    'create_time' => time(),
                    'update_time' => time(),
                );
                $ret = $daoCapital->insertRecords($profiles);
                if ($ret == false) {
                    $this->daoUser->rollback();
                } else {
                    $this->daoUser->commit();
                }
            }
        }
        if ($needTeacherCapital) {
            if ($ret == false) {
                $this->daoUser->rollback();
            } else {
                $profiles = array(
                    'uid' => intval($uid),
                    'operator' => OPERATOR,
                    'type' => $profile['type'],
                    'category' => Service_Data_Schedule::CATEGORY_TEACHER_RECHARGE,
                    'capital' => intval($capital),
                    'create_time' => time(),
                    'update_time' => time(),
                );
                $ret = $daoCapital->insertRecords($profiles);
                if ($ret == false) {
                    $this->daoUser->rollback();
                } else {
                    $this->daoUser->commit();
                }
            }
        }
        if ($profile['type'] == Service_Data_User_Profile::USER_TYPE_ADMIN) {
            $this->daoUser->commit();
        }
        
        return $ret;
    }

    public function createUserInfo ($profile) {
        $daoCapital = null;
        if (!empty($profile['student_capital']) || !empty($profile['teacher_capital'])) {
            $this->daoUser->startTransaction();
            $daoCapital = new Dao_Capital();
            $profile['student_capital'] = $profile['student_capital'] * 100;
            $profile['teacher_capital'] = $profile['teacher_capital'] * 100;
        }
        $capital_remark = empty($profile['capital_remark']) ? "" : $profile['capital_remark'];
        unset($profile['capital_remark']);

        $ret = $this->daoUser->insertRecords($profile);
        if (!empty($profile['student_capital'])) {
            if ($ret == false) {
                $this->daoUser->rollback();
            } else {
                $uid = $this->daoUser->getInsertId();
                $profiles = array(
                    'uid' => intval($uid),
                    'operator' => OPERATOR,
                    'type' => $profile['type'],
                    'category' => Service_Data_Schedule::CATEGORY_STUDENT_RECHARGE,
                    'capital' => intval($profile['student_capital']),
                    'capital_remark' => $capital_remark,
                    'create_time' => time(),
                    'update_time' => time(),
                );
                $ret = $daoCapital->insertRecords($profiles);
                if ($ret == false) {
                    $this->daoUser->rollback();
                } else {
                    $this->daoUser->commit();
                }
            }
        }
        if (!empty($profile['teacher_capital'])) {
            if ($ret == false) {
                $this->daoUser->rollback();
            } else {
                $uid = $this->daoUser->getInsertId();
                $profiles = array(
                    'uid' => intval($uid),
                    'operator' => OPERATOR,
                    'type' => $profile['type'],
                    'category' => Service_Data_Schedule::CATEGORY_TEACHER_RECHARGE,
                    'capital' => intval($profile['teacher_capital']),
                    'create_time' => time(),
                    'update_time' => time(),
                );
                $ret = $daoCapital->insertRecords($profiles);
                if ($ret == false) {
                    $this->daoUser->rollback();
                } else {
                    $this->daoUser->commit();
                }
            }
        }
        if ($profile['type'] == Service_Data_User_Profile::USER_TYPE_ADMIN) {
            $this->daoUser->commit();
        }
        return $ret;
    }

    public function deleteUserInfo ($uid) {
        $this->daoUser->startTransaction();
        $conds = array(
            'uid' => intval($uid),
        );
        $ret = $this->daoUser->deleteByConds($conds);
        if ($ret == false) {
            $this->daoUser->rollback();
            return false;
        }

        // 学生删掉关联的班级
        $daoGroupMap = new Dao_Groupmap();
        $conds = array(
            'student_id' => intval($uid),
        ) ;
        $ret = $daoGroupMap->deleteByConds($conds);
        if ($ret == false) {
            $this->daoUser->rollback();
            return false;
        }

        // 删掉关联的权限
        $daoRoles = new Dao_Rolesmap();
        $conds = array(
            'uid' => intval($uid),
        ) ;
        $ret = $daoRoles->deleteByConds($conds);
        if ($ret == false) {
            $this->daoUser->rollback();
            return false;
        }
        $this->daoUser->commit();
        return $ret;
    }

    public function deleteTeacher ($uid) {
        $this->daoUser->startTransaction();
        $daoColumn = new Dao_Column();
        $daoSchedule = new Dao_Schedule();

        $conds = array(
            'teacher_id' => $uid,
        );
        $columnInfo = $daoColumn->getListByConds($conds, array("id"));
        $columnInfo = array_column($columnInfo, "id");
        if (!empty($columnInfo)) {
            $conds = array(
                sprintf("column_id in (%s)", implode(",", $columnInfo)),
                "state = 1",
            );
            $ret = $daoSchedule->deleteByConds($conds);
            if ($ret === false) {
                $this->daoUser->rollback();
                return false;
            }
        }

        $conds = array(
            "teacher_id" => $uid,
        );
        $ret = $daoColumn->deleteByConds($conds);
        if ($ret === false) {
            $this->daoUser->rollback();
            return false;
        }
        $conds = array(
            'uid' => intval($uid),
        );
        $ret = $this->daoUser->deleteByConds($conds);
        if ($ret === false) {
            $this->daoUser->rollback();
            return false;
        }
        $this->daoUser->commit();
        return $ret;
    }

    public function setUserSession ($userInfo) {
        return Zy_Core_Session::getInstance()->setSessionUserInfo(
            $userInfo['uid'], 
            $userInfo['name'], 
            $userInfo['phone'], 
            $userInfo['type'],
            $userInfo['pages']);
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
        foreach ($lists as $index => $item) {
            $item['create_time']  = date('Y年m月d日', $item['create_time']);
            $item['update_time']  = date('Y年m月d日', $item['update_time']);
            $item['sexInfo']  = $item['sex'] == "M" ? "男" : "女";
            $item['student_capital_format']  = ($item['student_capital'] / 100) . "元";
            $item['teacher_capital_format']  = ($item['teacher_capital'] / 100) . "元";
            $item['state_info'] = $item['state'] == 1 ? "不可排课" : "可排课";
            $lists[$index] = $item;
        }
        return $lists;
    }

    public function getTotalByConds($conds) {
        return  $this->daoUser->getCntByConds($conds);
    }
}