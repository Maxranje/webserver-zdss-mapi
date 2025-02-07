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
        return (OPERATOR != $sopuid || $this->isModeAble($modeId));
    }    
}