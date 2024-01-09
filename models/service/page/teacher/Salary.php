<?php

class Service_Page_Teacher_Salary extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()  || !$this->isModeAble(Service_Data_Roles::ROLE_MODE_TEACHER_SALARY)) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $uid        = empty($this->request['uid']) ? 0 : intval($this->request['uid']);
        $duration   = empty($this->request['salary_duration']) ? 0 : floatval($this->request['salary_duration']);
        $duration   = floatval(sprintf("%.2f", $duration));

        if ($uid <= 0 || $duration < 0) {
            throw new Zy_Core_Exception(405, "操作失败, uid和时间必须大于0");
        }

        $serviceData = new Service_Data_Profile();
        $userInfo = $serviceData->getUserInfoByUid($uid);
        if (empty($userInfo) || $userInfo["state"] != Service_Data_Profile::STUDENT_ABLE) {
            throw new Zy_Core_Exception(405, "操作失败, 无法查到相关教师或已下线");
        }

        $ext = empty($userInfo['ext']) ? array() : json_decode($userInfo['ext'], true);
        if ($duration == 0) {
            unset($ext["salary"]);
        } else {
            $ext['salary'] = array(
                "duration" => $duration,
                "time" => time(),
                "operator" => OPERATOR,
            );
        }

        $profile = [
            "ext" => json_encode($ext),
        ];

        $ret = $serviceData->editUserInfo($uid, $profile);
        if ($ret === false) {
            throw new Zy_Core_Exception(405, "更新失败, 请重试");
        }
        return array();
    }
}