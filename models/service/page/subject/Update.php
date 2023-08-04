<?php

class Service_Page_Subject_Update extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $id         = empty($this->request['id']) ? 0 : intval($this->request['id']);
        $category = empty($this->request['category']) ? "" : $this->request['category'];
        $name      = empty($this->request['name']) ? "" : $this->request['name'];
        $descs     = empty($this->request['descs']) ? "" : $this->request['descs'];
        $price     = empty($this->request['price_info']) ? 0 : floatval($this->request['price_info']);

        if ($id <= 0) {
            throw new Zy_Core_Exception(405, "请求参数错误, 请检查");
        }

        if (empty($category) ||  empty($name) || $price < 0) {
            throw new Zy_Core_Exception(405, "分类和科目名不能为空, 客单价不能小于0, 请检查");
        }

        $serviceData = new Service_Data_Subject();
        $conds = array(
            "category" => $category,
            "name" => $name,
        );
        $subject = $serviceData->getRecordByConds($conds);
        if (!empty($subject) && $id != $subject['id']) {
            throw new Zy_Core_Exception(405, "分类和科目名已存在, 请检查");
        }
        
        $subject = $serviceData->getSubjectById($id);
        if (empty($subject)) {
            throw new Zy_Core_Exception(405, "无法查到相关科目数据");
        }

        $profile = [
            "category"      => $category, 
            "price"         => intval($price * 100), 
            "name"          => $name, 
            "descs"         => $descs, 
            "update_time"   => time(),
        ];

        $ret = $serviceData->editSubject($id, $profile);
        if ($ret === false) {
            throw new Zy_Core_Exception(405, "更新失败, 请重试");
        }
        return array();
    }
}