<?php

class Service_Page_Abroadplan_Delete extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $id = empty($this->request['id']) ? 0 : intval($this->request['id']);
        if ($id <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 需要选中留学计划");
        }

        $serviceData = new Service_Data_Abroadplan();
        $abroadplan = $serviceData->getAbroadplanById($id);
        if (empty($abroadplan)) {
            throw new Zy_Core_Exception(405, "操作失败, 留学计划不存在");
        }

        $serviceApackage = new Service_Data_Aporderpackage();
        $conds = array(
            "abroadplan_id" => $id,
        );
        $total = $serviceApackage->getTotalByConds($conds);
        if ($total > 0) {
            throw new Zy_Core_Exception(405, "操作失败,留学&升学计划存在关联的服务配置, 需要先删除服务再删除当前计划");
        }

        $ret = $serviceData->delete($id);
        if ($ret == false) {
            throw new Zy_Core_Exception(405, "删除错误, 请重试");
        }
        return array();
    }
}