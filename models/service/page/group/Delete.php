<?php

class Service_Page_Group_Delete extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $groupId = empty($this->request['group_id']) ? 0 : intval($this->request['group_id']);
        if ($groupId <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 需要选择班级");
        }

        // 查看是否有排课
        $serviceData = new Service_Data_Schedule();
        $count = $serviceData->getTotalByConds(array('group_id' => $groupId));
        if ($count > 0) {
            throw new Zy_Core_Exception(405, "操作失败, 有关联的排课无法删除");
        }

        $serviceData = new Service_Data_Group();
        $ret = $serviceData->delete($groupId);
        if ($ret == false) {
            throw new Zy_Core_Exception(405, "删除错误, 请重试");
        }
        
        return array();
    }
}