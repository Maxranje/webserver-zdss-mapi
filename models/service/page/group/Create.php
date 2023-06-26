<?php

class Service_Page_Group_Create extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $name       = empty($this->request['name']) ? "" : trim($this->request['name']);
        $descs      = empty($this->request['descs']) ? "" : trim($this->request['descs']);
        $price      = empty($this->request['price']) ? 0 : $this->request['price'];
        $duration   = empty($this->request['duration']) ? 0 : floatval($this->request['duration']);
        $discount   = empty($this->request['discount']) ? 0 : intval($this->request['discount']);
        $studentIds = empty($this->request['student_ids']) ? array() : explode(",", $this->request['student_ids']);
        $areaop     = empty($this->request['area_op']) ? 0 : intval($this->request['area_op']);
        
        if (empty($name) || $duration <= 0 || $areaop <= 0){
            throw new Zy_Core_Exception(405, "某些必填字段为空, 请检查表单填写项");
        }

        $serviceData = new Service_Data_Group();
        $profile = [
            "student_ids"   => $studentIds,
            "name"          => $name,
            "descs"         =>  $descs, 
            "price"         => intval($price * 100),
            "status"        => 1,
            'duration'      => sprintf("%.2f", $duration),
            'area_op'       => $areaop,
            'discount'      => $discount,
        ];

        $ret = $serviceData->createGroup($profile);
        if ($ret == false) {
            throw new Zy_Core_Exception(405, "创建失败, 请重试");
        }
        return array();
    }
}