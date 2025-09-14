<?php

class Service_Page_Mock_Paper_Addquestion extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限操作");
        }

        $qids  = empty($this->request['paper_add_qids']) ? array() : Zy_Helper_Utils::arrayInt(explode(",", $this->request['paper_add_qids']));
        $pid   = empty($this->request['pid']) ? 0 : intval($this->request['pid']);

        if ($pid <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 无法确认试卷");
        } 

        $servicePaper = new Service_Data_Paper();
        $paper = $servicePaper->getPaperById($pid);
        if (empty($paper)) {
            throw new Zy_Core_Exception(405, "操作失败, 试卷不存在或已被删除");
        }
        
        if ($paper["frequency"] > 0 && $paper["type"] == Service_Data_Paper::PAPER_TYPE_NORMAL) {
            throw new Zy_Core_Exception(405, "操作失败, 常规试卷在以被录入到了模考平台后不允许调整试题");
        }

        $oldQids = $servicePaper->getPaperQuestionIds($pid);

        // 试题
        $serviceQuestion = new Service_Data_Question();
        $questions = $serviceQuestion->getQuestionByIds($qids, true);
        if (empty($questions) || count($questions) != count($qids)) {
            throw new Zy_Core_Exception(405, "操作失败, 部分试题不存在或被删除, 请刷新检查");
        }               

        $addQids = array_diff($qids, $oldQids);
        $delQids = array_diff($oldQids, $qids);

        if (empty($addQids) && empty($delQids)) {
            throw new Zy_Core_Exception(405, "操作失败, 无更新内容");
        }

        $totalScore = 0;
        foreach ($questions as $v) {
            $totalScore += $v["score"];
        }

        $profile = array(
            "pid" => $pid,
            "total_score" => $totalScore,
            "questions" => $questions,
            "delQids" => $delQids,
            "addQids" => $addQids,
        );

        $serviceData = new Service_Data_Paper();
        $ret = $serviceData->addQuestion($profile);
        if ($ret == false) {
            throw new Zy_Core_Exception(405, "添加失败, 请重试");
        }
        return array();
    }
}