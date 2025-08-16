<?php

class Service_Page_Mock_Paper_Create extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限操作");
        }

        $title      = empty($this->request['title']) ? "" : trim($this->request['title']);
        $descs      = empty($this->request['descs']) ? "" : trim($this->request['descs']);
        $subjectIds = empty($this->request['subject_ids']) ? array() : explode(",",$this->request['subject_ids']);
        $sourceIds  = empty($this->request['source_ids']) ? array() : explode(",",$this->request['source_ids']);

        if (empty($title)) {
            throw new Zy_Core_Exception(405, "操作失败, 无标题");
        }

        if (!Zy_Helper_Utils::checkStr($title, 1, 100)) {
            throw new Zy_Core_Exception(405, "操作失败, 试卷名仅支持英文/中文/数字/逗号, 长度100个字内");
        }

        if (!empty($descs) && !Zy_Helper_Utils::checkStr($descs, 1, 200)) {
            throw new Zy_Core_Exception(405, "操作失败, 说明仅支持英文/中文/数字/逗号, 长度200个字内");
        }
        $sourceIds = Zy_Helper_Utils::arrayInt($sourceIds);
        $subjectIds = Zy_Helper_Utils::arrayInt($subjectIds);

        if (empty($sourceIds) || empty($subjectIds)) {
            throw new Zy_Core_Exception(405, "操作失败, 来源和分类未指定");
        }

        $serviceData = new Service_Data_Source();
        $sourceData = $serviceData->getSourceByIds($sourceIds);
        if (empty($sourceData) || count($sourceData) != count($sourceIds)) {
            throw new Zy_Core_Exception(405, "操作失败, 给定来源不存在或已被删除");
        }

        $serviceData = new Service_Data_Subject();
        $subjectData = $serviceData->getSubjectByIds($subjectIds);
        if (empty($subjectData) || count($subjectData) != count($subjectIds)) {
            throw new Zy_Core_Exception(405, "操作失败, 给定学科不存在或已被删除");
        }

        $profile = array(
            "subject_ids"   => $subjectIds,
            "source_ids"    => $sourceIds,
            "title"         => $title,
            "descs"         => $descs,
        );

        $serviceData = new Service_Data_Paper();
        $ret = $serviceData->create($profile);        
        if ($ret == false) {
            throw new Zy_Core_Exception(405, "创建失败, 请重试");
        }
        return array();
    }
}