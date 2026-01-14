<?php

class Service_Page_Mock_Question_Update extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin() && !$this->isModeAble(Service_Data_Roles::ROLE_MODE_MOCK_DONE)) {
            throw new Zy_Core_Exception(405, "无权限操作");
        }

        $qid    = empty($this->request['qid']) ? 0 : intval($this->request['qid']);
        $state  = empty($this->request['state']) ? 0 : intval($this->request['state']);

        if ($qid <= 0 || !in_array($state, Service_Data_Question::QUESTION_STATE_MAP)) {
            throw new Zy_Core_Exception(405, "操作失败, 部分参数为空, 请检查");
        }

        $serviceQuestion = new Service_Data_Question();
        $question = $serviceQuestion->getQuestionById($qid);
        if (empty($question)) {
            throw new Zy_Core_Exception(405, "操作失败, 试题不存在或已被删除");
        }

        if ($state == $question["state"]) {
            return array();
        }
        
        // 判断是否还有上课的map
        $ret = $serviceQuestion->updateState($qid, $state);
        if (!$ret) {
            throw new Zy_Core_Exception(405, "修改状态异常, 请重试");
        }
        
        return array();
    }
}