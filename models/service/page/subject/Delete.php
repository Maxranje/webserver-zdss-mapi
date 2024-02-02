<?php

class Service_Page_Subject_Delete extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $subjectId = empty($this->request['subject_id']) ? 0 : intval($this->request['subject_id']);
        if ($subjectId <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 科目id参数为空, 请检查");
        }

        $serviceSubject = new Service_Data_Subject();
        $subjectInfo = $serviceSubject->getSubjectById($subjectId);
        if (empty($subjectInfo)) {
            throw new Zy_Core_Exception(405, "操作失败, 科目或科目单项不存在");
        }

        $subjectLists = $serviceSubject->getSubjectByParentID($subjectId);
        if (!empty($subjectLists)) {
            throw new Zy_Core_Exception(405, "操作失败, 需要删掉科目下所有单项, 才能删掉科目");
        }

        $serviceData = new Service_Data_Order();
        $order = $serviceData->getTotalByConds(array('subject_id' => $subjectId));
        if ($order > 0) {
            throw new Zy_Core_Exception(405, "操作失败, 有订单绑定课程, 无法删除, 请检查");
        }

        $serviceData = new Service_Data_Column();
        $column = $serviceData->getTotalByConds(array('subject_id' => $subjectId));
        if ($column > 0) {
            throw new Zy_Core_Exception(405, "操作失败, 有教师绑定课程, 无法删除, 请检查");
        }

        $serviceData = new Service_Data_Group();
        $group = $serviceData->getTotalByConds(array('subject_id' => $subjectId));
        if ($group > 0) {
            throw new Zy_Core_Exception(405, "操作失败, 有班级绑定课程, 无法删除, 请检查");
        }

        $serviceData = new Service_Data_Claszemap();
        $claszeMap = $serviceData->getTotalByConds(array('subject_id' => $subjectId));
        if ($claszeMap > 0) {
            throw new Zy_Core_Exception(405, "操作失败, 有班型绑定课程, 无法删除, 请检查");
        }

        $ret = $serviceSubject->deleteSubject($subjectId);
        if ($ret == false) {
            throw new Zy_Core_Exception(405, "删除错误, 请重试");
        }
        
        return array();
    }
}