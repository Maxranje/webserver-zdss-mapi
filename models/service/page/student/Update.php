<?php

class Service_Page_Student_Update extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $uid        = empty($this->request['uid']) ? 0 : intval($this->request['uid']);
        $name       = empty($this->request['name']) ? "" : trim($this->request['name']);
        $phone      = empty($this->request['phone']) ? "" : trim($this->request['phone']);
        $nickname   = empty($this->request['nickname']) ? "" : trim($this->request['nickname']);
        $school     = empty($this->request['school']) ? "" : trim($this->request['school']);
        $graduate   = empty($this->request['graduate']) ? "" : trim($this->request['graduate']);
        $bpid       = empty($this->request['bpid']) ? 0 : intval($this->request['bpid']);
        $sex        = empty($this->request['sex']) ? "M" : trim($this->request['sex']);
        $sopuid     = empty($this->request['sop_uid']) ? 0 : intval($this->request['sop_uid']);
        $state      = empty($this->request["state"]) || !in_array($this->request['state'], Service_Data_Profile::STUDENT_STATE) ? Service_Data_Profile::STUDENT_ABLE : intval($this->request['state']);

        if ($uid <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 请求参数错误, 请检查");
        }

        if (empty($name) || empty($phone) || empty($nickname)) {
            throw new Zy_Core_Exception(405, "操作失败, 管理员名或手机号等提交数据有空值, 请检查");
        }

        if (!is_numeric($phone) || strlen($phone) < 6 || strlen($phone) > 12) {
            throw new Zy_Core_Exception(405, "操作失败, 手机号参数错误, 6-12位数字, 请检查");
        }

        if ($bpid <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 生源地必须填写");
        }

        if ($sopuid <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 学管必须要填写");
        }
        
        $serviceData = new Service_Data_Profile();
        $userInfo = $serviceData->getUserInfoByUid($uid);
        if (empty($userInfo)) {
            throw new Zy_Core_Exception(405, "操作失败, 无法查到相关用户");
        }

        $userInfo = $serviceData->getUserInfoByNameAndPhone($name, $phone);
        if (!empty($userInfo) && $userInfo['uid'] != $uid) {
            throw new Zy_Core_Exception(405, "操作失败, 用户名/手机号关联的账户已存在");
        }

        $profile = [
            "type"          => Service_Data_Profile::USER_TYPE_STUDENT , 
            "name"          => $name,
            "nickname"      => $nickname, 
            "phone"         => $phone, 
            "bpid"          => $bpid,
            "avatar"        => "",
            "school"        => $school, 
            "graduate"      => $graduate,
            "sex"           => $sex, 
            "state"         => $state,
            "update_time"   => time(),
        ];

        // 学生更新学管信息
        if (empty($userInfo['sop_uid']) || $userInfo['sop_uid'] != $sopuid) {
            $profile['sop_uid'] = $sopuid;
        }

        $ret = $serviceData->editUserInfo($uid, $profile);
        if ($ret === false) {
            throw new Zy_Core_Exception(405, "更新失败, 请重试");
        }
        return array();
    }
}