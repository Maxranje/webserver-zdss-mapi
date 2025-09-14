<?php

class Service_Page_Mock_Paper_Update extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限操作");
        }

        $pid        = empty($this->request['pid']) ? 0 : intval($this->request['pid']);
        $title          = empty($this->request['title']) ? "" : trim($this->request['title']);
        $type           = empty($this->request['type']) ? 0 : intval($this->request['type']);
        $remark         = empty($this->request['remark']) ? "" : trim($this->request['remark']);
        $subjectId      = empty($this->request['subject_id']) ? 0 : intval($this->request['subject_id']);
        $sourceIds      = empty($this->request['source_ids']) ? array() : Zy_Helper_Utils::arrayInt(explode(",", $this->request['source_ids']));

        if ($pid <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 必填参数为空");
        }

        if (empty($title) || !Zy_Helper_Utils::validateString($title, 1, 100)) {
            throw new Zy_Core_Exception(405, "操作失败, 标题必填且长度100个字内");
        }      

        if (!empty($remark) && !Zy_Helper_Utils::validateString($remark, 1, 200)) {
            throw new Zy_Core_Exception(405, "操作失败, 备注长度200个字内");
        }

        if (!in_array($type, Service_Data_Paper::PAPER_TYPE_MAP)) {
            throw new Zy_Core_Exception(405, "操作失败, 试卷类型不正确");
        }

        $servicePaper = new Service_Data_Paper();
        $paper = $servicePaper->getPaperById($pid);
        if (empty($paper)) {
            throw new Zy_Core_Exception(405, "操作失败, 试卷信息不存在或已被删除");
        }
        
        if ($paper["frequency"] > 0) {
            throw new Zy_Core_Exception(405, "操作失败, 试卷已经关联模考, 无法编辑修改, 请重新创建或删除模考记录");
        }

        if (!empty($sourceIds)) {
            $serviceData = new Service_Data_Source();
            $sourceData = $serviceData->getSourceByIds($sourceIds);
            if (empty($sourceData) || count($sourceData) != count($sourceIds)) {
                throw new Zy_Core_Exception(405, "操作失败, 给定来源不存在或已被删除");
            }
        }

        if ($subjectId > 0) {
            $serviceData = new Service_Data_Subject();
            $subjectData = $serviceData->getSubjectById($subjectId);
            if (empty($subjectData)) {
                throw new Zy_Core_Exception(405, "操作失败, 给定学科不存在或已被删除");
            }
        }

        $profile = array(
            "subject_id"    => $subjectId,
            "source_ids"    => $sourceIds,
            "type"          => $type,    
            "title"         => $title,
            "remark"        => $remark,
        );

        $serviceData = new Service_Data_Paper();
        $ret = $serviceData->update($pid, $profile);        
        if ($ret == false) {
            throw new Zy_Core_Exception(405, "编辑失败, 请重试");
        }
        return array();
    }
}