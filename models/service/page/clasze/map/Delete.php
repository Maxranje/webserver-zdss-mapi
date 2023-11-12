<?php

class Service_Page_Clasze_Map_Delete extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $id = empty($this->request['id']) ? 0 : intval($this->request['id']);
        if ($id <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 需要选中");
        }

        $serviceData = new Service_Data_Claszemap();
        $Clasze = $serviceData->getClaszemapById($id);
        if (empty($Clasze)) {
            throw new Zy_Core_Exception(405, "操作失败, 绑定关系不存在");
        }

        $ret = $serviceData->delete($id);
        if ($ret == false) {
            throw new Zy_Core_Exception(405, "删除错误, 请重试");
        }
        return array();
    }
}