<?php

class Service_Page_Subject_Delete extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $id = empty($this->request['id']) ? 0 : intval($this->request['id']);
        if ($id <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 科目id参数为空, 请检查");
        }

        $serviceData = new Service_Data_Column();
        $column = $serviceData->getTotalByConds(array('subject_id' => $id));
        if ($column > 0) {
            throw new Zy_Core_Exception(405, "操作失败, 有教师绑定课程, 无法删除, 请检查");
        }

        $serviceData = new Service_Data_Order();
        $order = $serviceData->getTotalByConds(array('subject_id' => $id));
        if ($order > 0) {
            throw new Zy_Core_Exception(405, "操作失败, 有订单绑定课程, 无法删除, 请检查");
        }

        $serviceData = new Service_Data_Subject();
        $ret = $serviceData->deleteSubject($id);
        if ($ret == false) {
            throw new Zy_Core_Exception(405, "删除错误, 请重试");
        }
        
        return array();
    }
}