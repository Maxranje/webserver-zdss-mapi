<?php

class Service_Page_Column_Delete extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $teacherId = empty($this->request['teacher_id']) ? 0 : intval($this->request['teacher_id']);
        $subjectId = empty($this->request['subject_id']) ? 0 : intval($this->request['subject_id']);

        if ($teacherId <= 0 || $subjectId <= 0) {
            throw new Zy_Core_Exception(405, "无法获取教师或科目, 请检查");
        }

        $serviceData = new Service_Data_Column();
        // 判断是否还有上课的map
        $status = $serviceData->deleteColumn($teacherId, $subjectId);
        if (!$status) {
            throw new Zy_Core_Exception(405, "删除错误或存在还在上的课, 请检查重试");
        }
        
        return array();
    }
}