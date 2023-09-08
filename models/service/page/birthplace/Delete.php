<?php

class Service_Page_Birthplace_Delete extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkSuper()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $id = empty($this->request['id']) ? 0 : intval($this->request['id']);
        if ($id <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 需要选中管理员");
        }

        $serviceData = new Service_Data_Birthplace();
        $birthplace = $serviceData->getBirthplaceById($id);
        if (empty($birthplace)) {
            throw new Zy_Core_Exception(405, "操作失败, 生源地不存在");
        }

        $ret = $serviceData->delete($id);
        if ($ret == false) {
            throw new Zy_Core_Exception(405, "删除错误, 请重试");
        }
        return array();
    }
}