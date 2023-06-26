<?php

class Service_Page_Column_Update extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $teacherId  = empty($this->request['teacher_id']) ? 0 : intval($this->request['teacher_id']);
        $subjectId  = empty($this->request['subject_id']) ? 0 : intval($this->request['subject_id']);
        $price      = empty($this->request['price']) ? 0 : floatval($this->request['price']);
        $number     = empty($this->request['number']) ? 0 : intval($this->request['number']);
        $muiltPrice = empty($this->request['muilt_price']) ? 0 : $this->request['muilt_price'];

        if ($teacherId <= 0 || $subjectId <= 0) {
            throw new Zy_Core_Exception(405, "部分参数为空, 请检查");
        }

        if ($number <= 1 && $muiltPrice > 0) {
            throw new Zy_Core_Exception(405, "阈值数量必须大于1, 才能设置价格");
        }

        if ($muiltPrice < 0 || $price < 0) {
            throw new Zy_Core_Exception(405, "单价或超阈值价格不能是负数");
        }

        $serviceSubject = new Service_Data_Subject();
        $subjectInfo = $serviceSubject->getSubjectById($subjectId);
        if (empty($subjectInfo)) {
            throw new Zy_Core_Exception(405, "科目不存在");
        }

        $serviceColumn = new Service_Data_Column();
        $column = $serviceColumn->getColumnByTSId($teacherId, $subjectId);
        if (empty($column)) {
            throw new Zy_Core_Exception(405, "课程不存在");
        }


        $conds = array(
            'teacher_id' => $teacherId,
            'subject_id' => $subjectId,
        );
        $profile = [
            "price"         => intval($price) * 100, 
            "number"        => $number,
            "muilt_price"   => intval($muiltPrice) * 100,
            "discount"      => 0,
            'update_time'   => time(),
        ];
        $ret = $serviceColumn->editColumn($conds, $profile);

        if ($ret === false) {
            throw new Zy_Core_Exception(405, "更新失败, 请重试");
        }
        return array();
    }
}