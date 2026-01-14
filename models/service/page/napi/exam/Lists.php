<?php

class Service_Page_Napi_Exam_Lists extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkStudent()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }
        $now = time();
        $uid = $this->adption["userid"];

        $serviceExam = new Service_Data_Exam();
        $studentExams = $serviceExam->getExamsBySuid($uid);
        if (empty($studentExams)) {
            return array();
        }

        $pids = Zy_Helper_Utils::arrayInt($studentExams, "pid");
        $examIds = Zy_Helper_Utils::arrayInt($studentExams, "exam_id");

        $servicePaper = new Service_Data_Paper();
        $paperInfos = $servicePaper->getPaperByIds($pids);
        $paperInfos = array_column($paperInfos, null, "pid");

        $examInfos = $serviceExam->getExamByIds($examIds);
        $examInfos = array_column($examInfos, null, "id");

        $teacherUids = Zy_Helper_Utils::arrayInt($examInfos, "teacher_uid");
        $serviceUser = new Service_Data_Profile();
        $teacherInfos = $serviceUser->getUserInfoByUids($teacherUids);
        $teacherInfos = array_column($teacherInfos, null, "uid");

        $ret = array(
            "history" => array(),
            "pending" => array(),
        );
        
        foreach ($studentExams as $item) {
            $pid = intval($item["pid"]);
            $examId = intval($item["exam_id"]);
            if (empty($paperInfos[$pid]["title"]) || empty($examInfos[$examId]["indentify"])) {
                continue;
            }
            
            $exam = $examInfos[$examId];
            $paper = $paperInfos[$pid];

            $tmp = array(
                // 考试
                "examId" => $examId,
                "type" => $paper["type"],
                "indentify" => $exam["indentify"],
                "updateTime" => $exam["update_time"],
                "startTime" => $exam["start_time"],
                "endTime" => $exam["end_time"],
                "status" => $exam["status"],
                "paperName" => $paper["title"],
                "expireTime" => intval($exam["expire_time"]) * 60,             
                "lastTime" => $exam["end_time"] <= $now  ? 0 : $exam["end_time"] - $now ,
                "teacherName" => empty($teacherInfos[$exam["teacher_uid"]]["nickname"]) ? "" : $teacherInfos[$exam["teacher_uid"]]["nickname"],

                // 学生信息
                "studentStartTime" => $item["start_time"],
                "studentEndTime" => $item["end_time"],
                "studentSpendTime" => intval($item["spend_time"]),
                "studentScore" => $item["status"] == Service_Data_Exam::EXAM_STUDENT_STATUS_FINISHED ? $item["score"] : 0,
                "studentStatus" => $item["status"],
                "sign" => ""
            );

            // 进行中
            if (($item["status"] == Service_Data_Exam::EXAM_STUDENT_STATUS_PENDING && $tmp["lastTime"] > 0) ||
                $item["status"] == Service_Data_Exam::EXAM_STUDENT_STATUS_ONGOING){
                $ret["pending"][] = $tmp;
            } else {
                $ret["history"][] = $tmp;
            }
        }

        return $ret;
    }
}