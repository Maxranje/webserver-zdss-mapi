<?php

class Service_Page_Area_Delete extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $rid = empty($this->request['rid']) ? 0 : intval($this->request['rid']);
        $aid = empty($this->request['aid']) ? 0 : intval($this->request['aid']);

        if ($rid <= 0 || $aid <= 0) {
            throw new Zy_Core_Exception(405, "部分参数为空, 请检查");
        }

        $serviceData = new Service_Data_Area();

        $area = $serviceData->getAreaById($aid, true);
        if (empty($area['rooms'])) {
            throw new Zy_Core_Exception(405, "校区信息中无教室");
        }

        $rooms = array_column($area['rooms'], null, 'id');
        if (empty($rooms[$rid])) {
            throw new Zy_Core_Exception(405, "校区没有这个教室");
        }

        $bothDel = false;
        if (count($rooms) == 1) {
            $bothDel = true;
        }

        $status = $serviceData->deleteRoom($aid, $rid, $bothDel);
        if (!$status) {
            throw new Zy_Core_Exception(405, "删除错误, 请重试");
        }
        
        return array();
    }
}