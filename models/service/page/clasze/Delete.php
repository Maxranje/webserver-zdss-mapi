<?php

class Service_Page_Clasze_Delete extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkSuper()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $id = empty($this->request['id']) ? 0 : intval($this->request['id']);
        if ($id <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 需要选中");
        }

        $serviceData = new Service_Data_Clasze();
        $Clasze = $serviceData->getClaszeById($id);
        if (empty($Clasze)) {
            throw new Zy_Core_Exception(405, "操作失败, 班型不存在");
        }

        $serviceMap = new Service_Data_Claszemap();
        $count = $serviceMap->getTotalByConds(array("cid" => $id));
        if ($count > 0) {
            throw new Zy_Core_Exception(405, "操作失败, 删除班型需要提前删除班型和科目的绑定关系");
        }

        $serviceMap = new Service_Data_Group();
        $count = $serviceMap->getTotalByConds(array("cid" => $id));
        if ($count > 0) {
            throw new Zy_Core_Exception(405, "操作失败, 删除班型需要提前删除应用这个班型的班级");
        }

        $serviceData = new Service_Data_Clasze();
        $Clasze = $serviceData->getClaszeById($id);
        if (empty($Clasze)) {
            throw new Zy_Core_Exception(405, "操作失败, 班型不存在");
        }

        $ret = $serviceData->delete($id);
        if ($ret == false) {
            throw new Zy_Core_Exception(405, "删除错误, 请重试");
        }
        return array();
    }
}