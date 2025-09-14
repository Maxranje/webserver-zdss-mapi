<?php

class Service_Page_Mock_Exam_Lists extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $pn             = empty($this->request['page']) ? 1 : intval($this->request['page']);
        $rn             = empty($this->request['perPage']) ? 20 : intval($this->request['perPage']);
        $pid            = empty($this->request['pid']) ? 0 : intval($this->request['pid']);
        $teacherUid     = empty($this->request['teacher_uid']) ? 0 : intval($this->request['teacher_uid']);
        $studentUid    = empty($this->request['student_uid']) ? 0 : intval($this->request['student_uid']);
        $status         = empty($this->request['status']) ? 0 : intval($this->request['status']);
        $dataRange      = empty($this->request['daterangee']) ? array() : explode(",", $this->request['daterangee']);

        $pn = ($pn-1) * $rn;

        $serviceExam = new Service_Data_Exam();

        $conds = array();
        if ($pid > 0) {
            $conds[] = sprintf("pid = %d", $pid);
        }

        if ($teacherUid > 0) {
            $conds[] = sprintf("teacher_uid = %d", $teacherUid);
        }  
        
        if ($status > 0) {
            $conds[] = sprintf("status = %d", $status);
        }          
        
        if ($studentUid > 0) {
            $examInfos = $serviceExam->getExamByStudentUids(array($studentUid));
            $examIds = empty($examInfos[$studentUid]) ? array() : array_column($examInfos[$studentUid], "exam_id");
            !empty($examIds) && $conds[] = sprintf("id in (%s)", implode(",",$examIds));
        }

        if (!empty($dataRange)) {
            $conds[] = sprintf("create_time >= %d", intval($dataRange[0]));
            $conds[] = sprintf("create_time <= %d", intval($dataRange[1]) + 1);
        }

        $arrAppends[] = "limit {$pn} , {$rn}";
        
        $lists = $serviceExam->getListByConds($conds, array(), NULL, $arrAppends);
        if (empty($lists)) {
            return array();
        }

        $lists = $this->formatDefault($lists);

        $total = $serviceExam->getTotalByConds($conds);
        return array(
            'rows' => $lists,
            'total' => $total,
        );
    }

    private function formatDefault ($lists) {

        $examIds = Zy_Helper_Utils::arrayInt($lists, "id");
        $pids = Zy_Helper_Utils::arrayInt($lists, "pid");
        $operators = Zy_Helper_Utils::arrayInt($lists, "operator");
        $teacherUids = Zy_Helper_Utils::arrayInt($lists, "teacher_uid");
        $uids = array_values(array_unique(array_merge($operators, $teacherUids)));

        $serviceData = new Service_Data_Profile();
        $userInfos = $serviceData->getUserInfoByUids($uids);
        $userInfos = array_column($userInfos, null, "uid");

        $serviceData = new Service_Data_Paper();
        $paperInfos = $serviceData->getPaperByIds($pids);
        $paperInfos = array_column($paperInfos, null, "pid");

        $serviceData = new Service_Data_Exam();
        $examMaps = $serviceData->getStudentByExamIds($examIds);

        $isModeDone = $this->isModeAble(Service_Data_Roles::ROLE_MODE_MOCK_DONE);
        
        $result = array();
        foreach ($lists as $item) {
            $item["is_terminated"] = $isModeDone || $item["teacher_uid"] == OPERATOR ? 1 : 0;
            $item["student_cnt"] = empty($examMaps[$item["id"]]) ? 0 : count($examMaps[$item["id"]]);
            $item["expire_time"] = $item["expire_time"] . "分钟";
            $item["start_end"] = sprintf("%s~%s", date("Y年m月d日 H:i:s", $item["start_time"]), date("Y年m月d日 H:i:s", $item["end_time"]));
            
            $item["paper_name"] = empty($paperInfos[$item["pid"]]["title"]) ? "-" : $paperInfos[$item["pid"]]["title"]; 
            $item["teacher_name"] = empty($userInfos[$item["teacher_uid"]]["nickname"]) ? "-" : $userInfos[$item["teacher_uid"]]["nickname"];
            $item["operator_name"] = empty($userInfos[$item["operator"]]["nickname"]) ? "-" : $userInfos[$item["operator"]]["nickname"];
            $result[] = $item;
        }
        return $result;
    }

}