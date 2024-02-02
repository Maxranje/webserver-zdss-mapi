<?php

class Service_Page_Teacher_Delete extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $uid  = empty($this->request['uid']) ? 0 : intval($this->request['uid']);
        if ($uid <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 请选定教师");
        }

        $serviceData = new Service_Data_Schedule();
        $count = $serviceData->getTotalByConds(array('teacher_uid' => $uid));
        if ($count > 0) {
            throw new Zy_Core_Exception(405, "操作失败, 教师有排课, 无法删除");
        }

        $serviceData = new Service_Data_Column();
        $count = $serviceData->getTotalByConds(array('teacher_uid' => $uid));
        if ($count > 0) {
            throw new Zy_Core_Exception(405, "操作失败, 教师和科目有绑定关系, 无法删除");
        }

        $serviceData = new Service_Data_Profile();
        $ret = $serviceData->deleteUserInfo($uid, Service_Data_Profile::USER_TYPE_TEACHER);
        if ($ret == false) {
            throw new Zy_Core_Exception(405, "删除错误, 请重试");
        }
        
        return array();
    }
}