<?php

class Service_Page_Mock_Review_Lists extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $pn             = empty($this->request['page']) ? 1 : intval($this->request['page']);
        $rn             = empty($this->request['perPage']) ? 20 : intval($this->request['perPage']);
        $pid            = empty($this->request['pid']) ? 0 : intval($this->request['pid']);
        $examIndentify  = empty($this->request['exam_indentify']) ? "" : trim($this->request['exam_indentify']);
        $teacherUid     = empty($this->request['teacher_uid']) ? 0 : intval($this->request['teacher_uid']);
        $studentUid     = empty($this->request['student_uid']) ? 0 : intval($this->request['student_uid']);
        $status         = empty($this->request['status']) ? 0 : intval($this->request['status']);
        $dataRange      = empty($this->request['daterange']) ? array() : explode(",", $this->request['daterange']);

        $pn = ($pn-1) * $rn;

        $serviceExam = new Service_Data_Exam();

        if ($pid > 0) {
            $conds[] = sprintf("pid = %d", $pid);
        }

        $examIds = array();
        if ($teacherUid > 0) {
            $teacherExam = $serviceExam->getExamByTeacherUid($teacherUid);
            $tmpExamIds = Zy_Helper_Utils::arrayInt($teacherExam, "id");
            if (empty($tmpExamIds)) {
                return array();
            }
            $examIds = array_merge($examIds, $tmpExamIds);
        }  

        if (!empty($examIndentify)) {
            $examInfo = $serviceExam->getExamByIndentify($examIndentify);
            if (empty($examInfo)) {
                return array();
            }
            $examIds = array_merge($examIds, array($examInfo['id']));
        }
        
        if (in_array($status, [
                Service_Data_Exam::EXAM_STUDENT_STATUS_REVIEWING,
                Service_Data_Exam::EXAM_STUDENT_STATUS_COMPLETE,
                Service_Data_Exam::EXAM_STUDENT_STATUS_FINISHED,
                Service_Data_Exam::EXAM_STUDENT_STATUS_TERMINATED,
            ])) {
            $conds[] = sprintf("status = %d", $status);
        } else {
            $conds[] = sprintf("status in (%s)", implode(",", [
                Service_Data_Exam::EXAM_STUDENT_STATUS_REVIEWING,
                Service_Data_Exam::EXAM_STUDENT_STATUS_COMPLETE,
                Service_Data_Exam::EXAM_STUDENT_STATUS_FINISHED,
                Service_Data_Exam::EXAM_STUDENT_STATUS_TERMINATED,
            ]));
        }
        
        if ($studentUid > 0) {
            $conds[] = sprintf("student_uid = %d", $studentUid);
        }

        if (!empty($examIds)) {
            $conds[] = sprintf("exam_id in (%s)", implode(",", $examIds));
        }

        if (!empty($dataRange)) {
            $conds[] = sprintf("start_time >= %d", intval($dataRange[0]));
            $conds[] = sprintf("start_time < %d", intval($dataRange[1]));
        }

        $arrAppends[] = "limit {$pn} , {$rn}";
        
        $lists = $serviceExam->getStudentListByConds($conds, array(), NULL, $arrAppends);
        if (empty($lists)) {
            return array();
        }

        $lists = $this->formatDefault($lists);
        $total = $serviceExam->getStudentTotalByConds($conds);
        return array(
            'rows' => $lists,
            'total' => $total,
        );
    }

    private function formatDefault ($lists) {

        $examIds = Zy_Helper_Utils::arrayInt($lists, "exam_id");
        $pids = Zy_Helper_Utils::arrayInt($lists, "pid");
        $operators = Zy_Helper_Utils::arrayInt($lists, "operator");
        $studentUids = Zy_Helper_Utils::arrayInt($lists, "student_uid");
        
        $serviceData = new Service_Data_Exam();
        $examInfos = $serviceData->getExamByIds($examIds);
        $examInfos = array_column($examInfos, null, "id");
        $teacherUids = Zy_Helper_Utils::arrayInt($examInfos, "teacher_uid");

        $uids = array_values(array_unique(array_merge($operators, $studentUids, $teacherUids)));

        $serviceData = new Service_Data_Profile();
        $userInfos = $serviceData->getUserInfoByUids($uids);
        $userInfos = array_column($userInfos, null, "uid");

        $serviceData = new Service_Data_Paper();
        $paperInfos = $serviceData->getPaperByIds($pids);
        $paperInfos = array_column($paperInfos, null, "pid");
        
        $result = array();
        foreach ($lists as $item) {
            if (empty($userInfos[$item["student_uid"]]["nickname"])) {
                continue;
            }
            if (empty($examInfos[$item["exam_id"]]['indentify'])) {
                continue;
            }
            $exam = $examInfos[$item["exam_id"]];
            $item["student_name"] = $userInfos[$item["student_uid"]]["nickname"];
            $item["indentify"]    = $exam['indentify'];
            $item["spend_time"] = Zy_Helper_Utils::formatDurationForTime($item["spend_time"], false, false);
            $item["start_end"] = sprintf("%s~%s", date("Y年m月d日 H:i", $item["start_time"]), date("H:i", $item["end_time"]));
            $item["exam_type"] = $exam["paper_type"];
            $item["paper_name"] = empty($paperInfos[$item["pid"]]["title"]) ? "-" : $paperInfos[$item["pid"]]["title"]; 
            $item["teacher_name"] = empty($userInfos[$exam["teacher_uid"]]["nickname"]) ? "-" : $userInfos[$exam["teacher_uid"]]["nickname"];
            $item["operator_name"] = empty($userInfos[$item["operator"]]["nickname"]) ? "-" : $userInfos[$item["operator"]]["nickname"];
            $item["student_score"] = $item["status"] == Service_Data_Exam::EXAM_STUDENT_STATUS_FINISHED ? $item["score"] . "分" : "-";
            unset($item["score"]);
            $result[] = $item;
        }
        return $result;
    }

}