<?php

class Service_Page_Subject_Update extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $subjectId      = empty($this->request['subject_id']) ? 0 : intval($this->request['subject_id']);
        $subjectName    = empty($this->request['subject_name']) ? "" : $this->request['subject_name'];
        $subjectDesc    = empty($this->request['subject_desc']) ? "" : $this->request['subject_desc'];
        $price          = empty($this->request['price_info']) ? 0 : floatval($this->request['price_info']);

        if ($subjectId <= 0 || empty($subjectName)) {
            throw new Zy_Core_Exception(405, "操作错误, 请求参数错误, 请检查");
        }

        $serviceData = new Service_Data_Subject();
        $subjectInfo = $serviceData->getSubjectById($subjectId);
        if (empty($subjectInfo)) {
            throw new Zy_Core_Exception(405, "操作错误, 科目不存在");
        }

        if ($subjectInfo['parent_id'] == 0) {
            if ($price < 0) {
                throw new Zy_Core_Exception(405, "操作错误, 客单价不能小于0");
            }
            
            // check name 
            $subInfo2 = $serviceData->getParentSubjectByName($subjectName);
            if (!empty($subInfo2) && $subInfo2['id'] != $subjectInfo['id']) {
                throw new Zy_Core_Exception(405, "操作错误, 科目名称有重复");
            }
        } else {
            $subInfo2 = $serviceData->getSubSubjectByName($subjectInfo['parent_id'], $subjectName);
            if (!empty($subInfo2) && $subInfo2['id'] != $subjectInfo['id']) {
                throw new Zy_Core_Exception(405, "操作错误, 科目单项名称有重复");
            }
        }

        $profile = [
            "name"          => $subjectName, 
            "price"         => intval($price * 100), 
            "descs"         => $subjectDesc, 
            "update_time"   => time(),
        ];

        $ret = $serviceData->editSubject($subjectId, $profile);
        if ($ret === false) {
            throw new Zy_Core_Exception(405, "更新失败, 请重试");
        }
        return array();
    }
}