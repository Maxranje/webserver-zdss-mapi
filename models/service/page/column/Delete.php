<?php

class Service_Page_Column_Delete extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }
        $id = empty($this->request['id']) ? 0 : intval($this->request['id']);

        if ($id <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 没有绑定ID信息");
        }

        $serviceColumn = new Service_Data_Column();
        $column = $serviceColumn->getColumnById($id);
        if (empty($column)) {
            throw new Zy_Core_Exception(405, "操作失败, 没有绑定信息");
        }

        $serviceData = new Service_Data_Schedule();
        $schedule = $serviceData->getTotalByConds(array('column_id' => $id));
        if ($schedule > 0) {
            throw new Zy_Core_Exception(405, "操作失败, 有关联的排课无法删除");
        }

        // 判断是否还有上课的map
        $status = $serviceColumn->deleteColumn($id);
        if (!$status) {
            throw new Zy_Core_Exception(405, "操作失败, 删除错误或存在还在上的课, 请检查重试");
        }
        
        return array();
    }
}