<?php

class Service_Page_Subject_Create extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $category1 = empty($this->request['category1']) ? "" : $this->request['category1'];
        $category2 = empty($this->request['category2']) ? "" : $this->request['category2'];
        $name      = empty($this->request['name']) ? "" : $this->request['name'];
        $descs     = empty($this->request['descs']) ? "" : $this->request['descs'];

        if (empty($category1) || empty($category2) || empty($name)) {
            throw new Zy_Core_Exception(405, "部分参数为空, 请检查");
        }

        $serviceData = new Service_Data_Subject();

        $conds = array(
            "category1" => $category1,
            "category2" => $category2,
            "name" => $name,
        );
        $subjectInfo = $serviceData->getListByConds($conds);
        if (!empty($subjectInfo)) {
            throw new Zy_Core_Exception(405, "科目名已存在, 请检查");
        }

        $profile = [
            "category1"  => $category1, 
            "category2"  => $category2, 
            "name"       => $name, 
            "descs"      => $descs, 
            "create_time" => time(),
            "update_time" => time(),
        ];

        $ret = $serviceData->createSubject($profile);
        if ($ret == false) {
            throw new Zy_Core_Exception(405, "创建失败, 请重试");
        }
        return $ret;
    }
}