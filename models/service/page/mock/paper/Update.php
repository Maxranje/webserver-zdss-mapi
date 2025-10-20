<?php

class Service_Page_Mock_Paper_Update extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限操作");
        }

        $pid            = empty($this->request['pid']) ? 0 : intval($this->request['pid']);
        $title          = empty($this->request['title']) ? "" : trim($this->request['title']);
        $type           = empty($this->request['type']) ? 0 : intval($this->request['type']);
        $remark         = empty($this->request['remark']) ? "" : trim($this->request['remark']);

        if ($pid <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 必填参数为空");
        }

        if (empty($title) || !Zy_Helper_Utils::validateString($title, 1, 50)) {
            throw new Zy_Core_Exception(405, "操作失败, 标题必填且长度50个字内");
        }      

        if (!empty($remark) && !Zy_Helper_Utils::validateString($remark, 1, 100)) {
            throw new Zy_Core_Exception(405, "操作失败, 备注长度100个字内");
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

        // 从评估切位常规, 需要判断题目数
        if ($paper["type"] != $type && $type == Service_Data_Paper::PAPER_TYPE_NORMAL) {
            $qids = $servicePaper->getPaperQuestionIds($pid);
            if (count($qids) > Service_Data_Paper::NORMAL_TOTAL_QUESTION) {
                throw new Zy_Core_Exception(405, "操作失败, 当前试卷考题数超过了".Service_Data_Paper::NORMAL_TOTAL_QUESTION."道, 无法变更为常规试卷");
            }
        }

        $profile = array(
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