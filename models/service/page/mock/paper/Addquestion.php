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
        if (count($qids) <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 试卷最少要有一道题");
        }

        $servicePaper = new Service_Data_Paper();
        $paper = $servicePaper->getPaperById($pid);
        if (empty($paper)) {
            throw new Zy_Core_Exception(405, "操作失败, 试卷不存在或已被删除");
        }
        
        if ($paper["frequency"] > 0 && $paper["type"] == Service_Data_Paper::PAPER_TYPE_NORMAL) {
            throw new Zy_Core_Exception(405, "操作失败, 已进行模考的常规试卷, 不允许调整试题");
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
        if ($paper["frequency"] > 0 && !empty($delQids) && $paper["type"] == Service_Data_Paper::PAPER_TYPE_ASSESS) {
            throw new Zy_Core_Exception(405, "操作失败, 已参与考试的评估试卷, 只能新增不能删除试题");
        }   
        if ($paper["type"] == Service_Data_Paper::PAPER_TYPE_NORMAL && count($qids) > Service_Data_Paper::INPUT_NORMAL_TOTAL_QUESTION) {
            throw new Zy_Core_Exception(405, "操作失败, 常规试卷最大录入".Service_Data_Paper::INPUT_NORMAL_TOTAL_QUESTION."道");
        }
        if ($paper["type"] == Service_Data_Paper::PAPER_TYPE_ASSESS && count($qids) > Service_Data_Paper::INPUT_ASSESS_TOTAL_QUESTION) {
            throw new Zy_Core_Exception(405, "操作失败, 评估试卷最大录入".Service_Data_Paper::INPUT_ASSESS_TOTAL_QUESTION."道");
        }

        // 增量去掉已下线资源
        $questions = array_column($questions, null, "qid");
        foreach ($addQids as $v) {
            if ($questions[$v]["state"] != Service_Data_Question::QUESTION_ABLE) {
                throw new Zy_Core_Exception(405, "操作失败, 增量试题中存在已下线试题, qid:%s, 描述:%s", $questions[$v]["qid"], $questions[$v]["description"]);
            }
        }

        $totalScore = 0;
        foreach ($questions as $v) {
            $totalScore += $v["score"];
        }

        $profile = array(
            "pid" => $pid,
            "total_score" => $totalScore,
            "questions" => $questions,
            "del_qids" => $delQids,
            "add_qids" => $addQids,
            "total_question" => count($qids),
        );

        $serviceData = new Service_Data_Paper();
        $ret = $serviceData->addQuestion($profile);
        if ($ret == false) {
            throw new Zy_Core_Exception(405, "添加失败, 请重试");
        }
        return array();
    }
}