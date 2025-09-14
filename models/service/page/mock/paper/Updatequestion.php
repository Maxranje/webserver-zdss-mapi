<?php

class Service_Page_Mock_Paper_Updatequestion extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限操作");
        }

        $pid   = empty($this->request['pid']) ? 0 : intval($this->request['pid']);
        $qid   = empty($this->request['qid']) ? 0 : intval($this->request['qid']);
        $score = empty($this->request['score']) ? 0 : intval($this->request['score']);

        if ($pid <= 0 || $qid <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 无法确认试卷或试题");
        } 

        if ($score <= 0 || $score > 100) {
            throw new Zy_Core_Exception(405, "操作失败, 分值必须在1到99之间");
        }

        $servicePaper = new Service_Data_Paper();
        $paper = $servicePaper->getPaperById($pid);
        if (empty($paper)) {
            throw new Zy_Core_Exception(405, "操作失败, 试卷不存在或已被删除");
        }
        
        if ($paper["frequency"] > 0 && $paper["type"] == Service_Data_Paper::PAPER_TYPE_NORMAL) {
            throw new Zy_Core_Exception(405, "操作失败, 常规试卷在以被录入到了模考平台后不允许调整试题及分值");
        }

        $questions = $servicePaper->getPaperQuestions($pid);
        $questions = array_column($questions, null, "qid");
        if (empty($questions[$qid]["id"])) {
            throw new Zy_Core_Exception(405, "操作失败, 试题关联不存在或已被从试卷中摘除");
        }
        if ($questions[$qid]["score"] == $score) {
            return array();
        }

        $ret = $servicePaper->updateQuestionScore($questions[$qid]['id'], $score);
        if ($ret == false) {
            throw new Zy_Core_Exception(405, "修改失败, 请重试");
        }
        return array();
    }
}