<?php

class Service_Page_Column_Create extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $teacherId = empty($this->request['uid']) ? 0 : intval($this->request['uid']);
        $subjectId = empty($this->request['subject_id']) ? 0 : intval($this->request['subject_id']);
        $price      = empty($this->request['price']) ? 0 : $this->request['price'];
        $duration   = empty($this->request['duration']) ? 0 : intval($this->request['duration']);
        $number   = empty($this->request['number']) ? 0 : intval($this->request['number']);
        $muiltPrice = empty($this->request['muilt_price']) ? 0 : $this->request['muilt_price'];

        if ($teacherId <= 0 || $subjectId <= 0) {
            throw new Zy_Core_Exception(405, "无法获取教师或科目, 请检查");
        }

        if ($number <= 1 && $muiltPrice > 0) {
            throw new Zy_Core_Exception(405, "阈值数量必须大于1, 才能设置价格");
        }

        if ($muiltPrice < 0 || $price < 0) {
            throw new Zy_Core_Exception(405, "单价或超阈值价格不能是负数");
        }

        $serviceData = new Service_Data_Column();
        $column = $serviceData->getColumnByTSId($teacherId, $subjectId);
        if (!empty($column)) {
            throw new Zy_Core_Exception(405, "已经绑定无需重新绑定");
        }

        $profile = [
            "subject_id"    => $subjectId, 
            "teacher_id"    => $teacherId, 
            "price"         => intval($price) * 100, 
            "duration"      => $duration, 
            "number"        => $number,
            "muilt_price"   => intval($muiltPrice) * 100,
            'update_time'   => time(),
            'create_time'   => time(),
        ];

        $ret = $serviceData->createColumn($profile);
        if ($ret == false) {
            throw new Zy_Core_Exception(405, "创建失败, 请重试");
        }
        return array();
    }
}