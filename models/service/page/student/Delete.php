<?php

class Service_Page_Student_Delete extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $uid  = empty($this->request['uid']) ? 0 : intval($this->request['uid']);
        if ($uid <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 请选择学员");
        }

        $serviceData = new Service_Data_Profile();
        $userInfo = $serviceData->getUserInfoByUid($uid);
        if (empty($userInfo)) {
            throw new Zy_Core_Exception(405, "操作失败, 学员不存在");
        }

        $serviceOrder = new Service_Data_Order();
        $orderCount = $serviceOrder->getTotalByConds(array('student_uid' => $uid));
        if ($orderCount > 0) {
            throw new Zy_Core_Exception(405, "操作失败, 学员存在订单关联无法删除");
        }

        $ret = $serviceData->deleteUserInfo($uid, $userInfo['type']);
        if ($ret == false) {
            throw new Zy_Core_Exception(405, "删除错误, 请重试");
        }
        
        return array();
    }
}