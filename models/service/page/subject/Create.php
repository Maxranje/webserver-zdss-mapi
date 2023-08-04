<?php

class Service_Page_Subject_Create extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $category = empty($this->request['category']) ? "" : $this->request['category'];
        $name      = empty($this->request['name']) ? "" : $this->request['name'];
        $descs     = empty($this->request['descs']) ? "" : $this->request['descs'];
        $price     = empty($this->request['price']) ? 0 : floatval($this->request['price']);

        if (empty($category) ||  empty($name) || $price < 0) {
            throw new Zy_Core_Exception(405, "分类和科目名不能为空, 客单价不能小于0, 请检查");
        }

        $serviceData = new Service_Data_Subject();

        $conds = array(
            "category" => $category,
            "name" => $name,
        );
        $subject = $serviceData->getRecordByConds($conds);
        if (!empty($subject)) {
            throw new Zy_Core_Exception(405, "分类和科目名已存在, 请检查");
        }

        $profile = [
            "category"      => $category, 
            "price"         => intval($price * 100), 
            "name"          => $name, 
            "descs"         => $descs, 
            "create_time"   => time(),
            "update_time"   => time(),
        ];

        $ret = $serviceData->createSubject($profile);
        if ($ret == false) {
            throw new Zy_Core_Exception(405, "创建失败, 请重试");
        }
        return $ret;
    }
}