<?php

class Service_Page_Column_Create extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $teacherUid     = empty($this->request['uid']) ? 0 : intval($this->request['uid']);
        $subjectId      = empty($this->request['subject_id']) ? 0 : intval($this->request['subject_id']);
        $price          = empty($this->request['price']) ? 0 : intval($this->request['price'] * 100);
        $muiltNum       = empty($this->request['muilt_num']) ? 0 : intval($this->request['muilt_num']);
        $muiltPrice     = empty($this->request['muilt_price']) ? 0 : intval($this->request['muilt_price'] * 100);

        if ($teacherUid <= 0 || $subjectId <= 0 || empty($price)) {
            throw new Zy_Core_Exception(405, "操作错误, 无法获取教师或科目或没有配置阈值客单价, 请检查");
        }

        if ($muiltNum < 0 || $muiltNum == 1 || ($muiltNum > 1 && $muiltPrice <= 0)) {
            throw new Zy_Core_Exception(405, "操作错误, 多人阈值配置信息有误, 请检查阈值和价格");
        }

        if ($muiltNum == 0) {
            $muiltPrice = 0;
        }
    
        $serviceData = new Service_Data_Subject () ;
        $subjectInfo = $serviceData->getSubjectById($subjectId);
        if (empty($subjectInfo) || empty($subjectInfo['parent_id'])) {
            throw new Zy_Core_Exception(405, "操作失败, 科目不存在, 或不是科目单项");
        }

        $serviceData = new Service_Data_Column();
        $column = $serviceData->getColumnByTSId($teacherUid, $subjectId);
        if (!empty($column)) {
            throw new Zy_Core_Exception(405, "操作失败, 已经绑定无需重新绑定");
        }

        $profile = [
            "subject_id"    => $subjectId, 
            "teacher_uid"   => $teacherUid, 
            "price"         => $price,
            "muilt_price"   => $muiltPrice,
            "muilt_num"     => $muiltNum, 
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