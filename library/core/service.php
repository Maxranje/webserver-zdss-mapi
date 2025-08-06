<?php

class Zy_Core_Service {
    
    public $request ;
    public $adption ; 

    public function __construct($request = array(), $adption = array()) {
        $this->request = $request;
        $this->adption = $adption;
    }

    // 检测是否是超管,  管理员或老师
    public function checkAdmin () {
        if (empty($this->adption['type']) 
            || !in_array($this->adption['type'], Service_Data_Profile::ADMIN_GRANT)) {
            return false;
        }
        return true;
    }

    public function checkSuper () {
        if (empty($this->adption['type']) 
            || $this->adption['type'] != Service_Data_Profile::USER_TYPE_SUPER) {
            return false;
        }
        return true;
    }

    public function checkTeacher () {
        if (empty($this->adption['type']) 
            || $this->adption['type'] != Service_Data_Profile::USER_TYPE_TEACHER) {
            return false;
        }
        return true;
    }

    public function checkStudent () {
        if (empty($this->adption['type']) 
            || $this->adption['type'] != Service_Data_Profile::USER_TYPE_STUDENT) {
            return false;
        }
        return true;
    }

    public function checkPartner () {
        if (empty($this->adption['type']) 
            || $this->adption['type'] != Service_Data_Profile::USER_TYPE_PARTNER) {
            return false;
        }
        return true;
    }

    public function checkTeacherPages () {
        if ($this->checkTeacher() && !empty($this->adption['pages'])) {
            return true;
        }
        return false;
    }    

    // 获取用户的权限ID
    public function getUserRolePageIds () {
        return empty($this->adption['pages']) ? array() : $this->adption['pages'];
    }

    // 获取用户功能权限id
    public function getUserRoleModeIds () {
        return empty($this->adption['modes']) ? array() : $this->adption['modes'];
    }

    // 功能是否可用
    public function isModeAble ($modeId) {
        if ($this->checkSuper()) {
            return true;
        }
        $modes = $this->getUserRoleModeIds();
        return in_array($modeId, $modes) ? true : false;
    }

    public function getPartnerBpid($partnerUid) {
        $serviceUser = new Service_Data_Profile();
        $userInfo = $serviceUser->getUserInfoByUid(intval($partnerUid));
        if (empty($userInfo['bpid']) || intval($userInfo['bpid']) <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 合作方信息不完整, 请联系管理员");
        }
        return intval($userInfo['bpid']);
    }

    // 是否有权限并且是学管
    public function isOperator ($modeId, $sopuid) {
        if ($this->checkSuper()) {
            return true;
        }
        return (OPERATOR == $sopuid || $this->isModeAble($modeId));
    }    

    // 写日志
    public function addOperationLog () {
        $point = Zy_Helper_Reg::get("point_log", 0);
        $workId = Zy_Helper_Reg::get("point_workid", 0);
        $from = Zy_Helper_Reg::get("point_from", "");
        $to = Zy_Helper_Reg::get("point_to", "");

        if ($point > 0 && $workId > 0 && !empty($from) && !empty($to)) {
            // 日志能否写进去都可以
            try {
                (new Service_Data_Operationlog())->writeLog($point, $workId, $from, $to);
            } catch (Exception $e) {
                Zy_Helper_Log::warning("add 42 ponit log failed, err:" . $e->getMessage());
            }
        }
    }

    // y用户登录信息
    public function getAuthInfo ($userInfo) {
        $roleType = 0 ;
        if ($userInfo["type"] == Service_Data_Profile::USER_TYPE_SUPER||  
            !empty($this->adption["pages"]) || 
            !empty($userInfo["pages"])) {
            $roleType = 1;
        }
        return array(
            "user" => array(
                "nickname"  => $userInfo["nickname"],
                "type"      => $userInfo["type"],
                "uid"       => $userInfo["uid"],
                "avatar"    => $userInfo["avatar"],    
                "school"    => $userInfo["school"],
                "graduate"  => $userInfo["graduate"],
                "roleType"  => $roleType,
            ),
            "auth_token" => Zy_Helper_Authtoken::buildToken($userInfo["uid"]),
        );
    }
}