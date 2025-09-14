<?php

class Service_Page_Mock_Paper_Delete extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限操作");
        }

        $pid = empty($this->request['pid']) ? 0 : intval($this->request['pid']);

        if ($pid <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 部分参数为空, 请检查");
        }

        $serviceData = new Service_Data_Paper();
        $paper = $serviceData->getPaperById($pid);
        
        if (empty($paper)) {
            throw new Zy_Core_Exception(405, "操作失败, 试卷不存在或已被删除");
        }

        if ($paper["frequency"] > 0) {
            throw new Zy_Core_Exception(405, "操作失败, 试卷已经关联模考, 不允许修改或删除");
        }        

        $status = $serviceData->delete($pid);
        if (!$status) {
            throw new Zy_Core_Exception(405, "删除错误, 请重试");
        }
        
        return array();
    }
}