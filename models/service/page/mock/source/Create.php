<?php

class Service_Page_Mock_Source_Create extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限操作");
        }

        $name       = empty($this->request['name']) ? "" : trim($this->request['name']);
        $subName    = empty($this->request['sub_name']) ? "" : trim($this->request['sub_name']);
        $id         = empty($this->request['id']) ? 0 : intval($this->request['id']);

        if (empty($name) || empty($subName)) {
            throw new Zy_Core_Exception(405, "操作失败, 参数错误");
        }

        if (!Zy_Helper_Utils::checkStr($name, 3, 10)) {
            throw new Zy_Core_Exception(405, "操作失败, 一级分类名仅支持英文/中文/数字/逗号, 长度最小3字最大10个字");
        }

        if (!Zy_Helper_Utils::checkStr($name, 3, 10)) {
            throw new Zy_Core_Exception(405, "操作失败, 二级分类名仅支持英文/中文/数字/逗号, 长度最小3字最大10个字");
        }    

        $serviceData = new Service_Data_Source();
        $source = array();
        if ($id > 0) {
            $source = $serviceData->getSourceById($id);
            if (empty($source)) {
                throw new Zy_Core_Exception(405, "操作失败, 一级来源不存在, 请刷新重试");
            }
            $source = $serviceData->getSourceByName($subName);
            if (!empty($source) && $source["parent_id"] == $id) {
                throw new Zy_Core_Exception(405, "操作失败, 一级来源下二级来源不能重名");
            }
        } else {
            $source = $serviceData->getSourceByName($name);
            if (!empty($source)) {
                throw new Zy_Core_Exception(405, "操作失败, 一级来源不能重名");
            }
        }
        $ret = $serviceData->create($id, $name, $subName);        
       if ($ret == false) {
            throw new Zy_Core_Exception(405, "创建失败, 请重试");
        }
        return array();
    }
}