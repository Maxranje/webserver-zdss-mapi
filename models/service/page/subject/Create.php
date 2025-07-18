<?php

class Service_Page_Subject_Create extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $subjectName    = empty($this->request['subject_name']) ? "" : $this->request['subject_name'];
        $subjectDesc    = empty($this->request['subject_desc']) ? "" : $this->request['subject_desc'];
        $identify       = empty($this->request['identify']) ? "" : $this->request['identify'];

        // 单项的
        $subjectId      = empty($this->request['subject_id']) ? 0 : intval($this->request['subject_id']);
        if ($subjectId > 0) {
            $subjectName    = empty($this->request['subject_item_name']) ? "" : $this->request['subject_item_name'];
            $subjectDesc    = empty($this->request['subject_item_desc']) ? "" : $this->request['subject_item_desc'];
            $price          = 0;
        }

        if (empty($subjectName)) {
            throw new Zy_Core_Exception(405, "操作失败, 必填参数不能为空");
        }

        if ($subjectId <= 0 && empty($identify)) {
            throw new Zy_Core_Exception(405, "操作失败, 科目标识不能为空");
        }

        $serviceData = new Service_Data_Subject();
        if ($subjectId <= 0 ) {
            $subjectInfo = $serviceData->getParentSubjectByName($subjectName);
            if (!empty($subjectInfo)) {
                throw new Zy_Core_Exception(405, "操作失败, 科目已经存在了");
            }
        } else {
            $subjectInfo = $serviceData->getSubSubjectByName($subjectId, $subjectName);
            if (!empty($subjectInfo)) {
                throw new Zy_Core_Exception(405, "操作失败, 科目单项已经存在了");
            }
        }

        $profile = [
            "name"              => $subjectName,
            "descs"             => $subjectDesc,
            'parent_id'         => $subjectId,
            'identify'          => $identify,
            "create_time"       => time(),
            "update_time"       => time(),
        ];

        $ret = $serviceData->createSubject($profile);
        if ($ret == false) {
            throw new Zy_Core_Exception(405, "创建失败, 请重试");
        }
        return $ret;
    }
}