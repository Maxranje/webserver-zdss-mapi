<?php

class Service_Page_Mock_Question_Delete extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限操作");
        }

        $qid = empty($this->request['qid']) ? 0 : intval($this->request['qid']);

        if ($qid <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 部分参数为空, 请检查");
        }

        $serviceQuestion = new Service_Data_Question();
        $question = $serviceQuestion->getQuestionById($qid);
        if (empty($question)) {
            throw new Zy_Core_Exception(405, "操作失败, 试题不存在或已被删除");
        }

        // 看看试题有没有考过试
        $serviceData = new Service_Data_Paper();
        $paperInfos = $serviceData->getPaperIdsByQids(array($qid), true);
        if (!empty($paperInfos[$qid])) {
            throw new Zy_Core_Exception(405, "操作失败, 已被试卷收录先从试卷中摘除后再删除试题");
        }        

        // 判断是否还有上课的map
        $status = $serviceQuestion->deleteBatch($qid);
        if (!$status) {
            throw new Zy_Core_Exception(405, "删除错误, 请重试");
        }
        
        return array();
    }
}