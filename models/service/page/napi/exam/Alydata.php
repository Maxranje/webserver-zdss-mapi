<?php

class Service_Page_Napi_Exam_Alydata extends Service_Page_Napi_Exam_Service{

    public function execute () {
        if (!$this->checkMockStudent()) {
            throw new Zy_Core_Exception(405, "无权限");
        }

        $uid    = $this->adption["userid"];
        $range  = empty($this->request['range']) ? 10 : intval($this->request['range']);
        if (!in_array($range, [10,20])) {
            $range = 10;
        }

        // 拉exam数据
        $serviceExam = new Service_Data_Exam();
        $conds = array(
            "student_uid" => $uid,
            "status" => Service_Data_Exam::EXAM_STUDENT_STATUS_FINISHED
        );
        $appends = array(
            "order by end_time desc",
            "limit " . $range,
        );
        $studentExam = $serviceExam->getStudentListByConds($conds, array(), null, $appends);
        if (empty($studentExam)) {
            throw new Zy_Core_Exception(405, "无已结束的模考数据");
        }

        // 拉试卷标题
        $servicePaper = new Service_Data_Paper();
        $paperInfos = $servicePaper->getPaperByIds(Zy_Helper_Utils::arrayInt($studentExam, "pid"));
        if (empty($paperInfos)) {
            throw new Zy_Core_Exception(405, "试卷信息异常");
        }
        $paperInfos = array_column($paperInfos, null, "pid");

        // 拉取所有作答信息
        $conds = array(
            "student_uid" => $uid,
            sprintf("exam_id in (%s)", implode(",", Zy_Helper_Utils::arrayInt($studentExam, "exam_id"))),
        );
        $fileds = (new Dao_ExamAnswer())->simpleFieldsMap;
        $studentAnswers = $serviceExam->getStudentAnswerListByConds($conds, $fileds);
        if (empty($studentAnswers)) {
            throw new Zy_Core_Exception(405, "无已结束的模考数据");
        }

        // 获取答案正确性
        $studentExam = $serviceExam->getMuiltExamAnswerDetail($studentExam, $studentAnswers);

        // 输出
        $output = array(
            "alycard" => array( // 顶部卡
                "overallAccuracy" => array(
                    "num" => 0,
                    "trend" => 0,
                ), // 正确率
                "averageScore" => array(
                    "num" => 0,
                    "trend" => 0,                
                ), // 平均分
                "totalErrors" => array(
                    "num" => 0,
                    "trend" => 0,                
                ), // 错误数                
            ),
            "alyerrtags" => array(),
            "alytrendChat" => array(
                "score" => array(),
                "accuracy" => array(),
                "labels" => array(),                
            ),
        );
        
        // 格式化 (以学员作答结束时间为基准)
        $before = $last = array(
            "score" => 0,
            "correct" => 0,
            "errcount" => 0,
        );
        $errTagQidsArr = array();
        foreach ($studentExam as $i => $v) {
            $totalCorrect =  $beforCorrect = $totalError = $beforeError = 0;
            if (!empty($v["studentAnswerRet"])) { // studentAnswerRet qid => correct score type
                foreach ($v["studentAnswerRet"] as $qid => $qv) {
                    if ($i == 0 ) {
                        $last["correct"] += $qv["is_correct"];
                        if (in_array($qv["type"], Service_Data_Question::QUESTION_TYPE_SIMPLE_MAP)) {
                            $last["errcount"] += $qv["is_correct"] == 1 ? 0 : 1;
                        }
                    }
                    $beforCorrect += $qv["is_correct"];
                    $totalCorrect += $qv["is_correct"];

                    if (in_array($qv["type"], Service_Data_Question::QUESTION_TYPE_SIMPLE_MAP)) {
                        $beforeError += $qv["is_correct"] == 1 ? 0 : 1;
                        $totalError += $qv["is_correct"] == 1 ? 0 : 1;
                    }
                    $errTagQidsArr[] = $qid;
                }
                // 最近一单场
                if ($i == 0) {
                    $last["correct"] = sprintf("%.2f", $last["correct"] / count($v["studentAnswerRet"])) * 100;
                }
                // 前n场正确率想加
                $before["correct"] += sprintf("%.2f", $beforCorrect / count($v["studentAnswerRet"])) * 100;
                // 全部
                $output['alycard']["overallAccuracy"]['num'] += sprintf("%.2f", $totalCorrect / count($v["studentAnswerRet"])) * 100;

                // 错误
                $before["errcount"] = $beforeError;
                $output["alycard"]["totalErrors"]['num'] = $totalError;
            }            
            if ($i == 0) {
                $last["score"] = $v["score"];
            }
            $before["score"] += $v["score"];
            $output["alycard"]["averageScore"]["num"] += $v["score"];

            // 正确率图标
            $output["alytrendChat"]["score"][] = $v["score"];
            $output["alytrendChat"]["accuracy"][] = empty($v["studentAnswerRet"]) ? 0 : sprintf("%.2f", ($totalCorrect / count($v["studentAnswerRet"])) * 100);
            $output["alytrendChat"]["labels"][] = empty($paperInfos[$v["pid"]]["title"]) ? date("Ymd H", $v["start_time"]) : $paperInfos[$v["pid"]]["title"];
        }
        // 平均分
        $output["alycard"]["averageScore"]["num"]  = floatval(sprintf("%.2f", $output["alycard"]["averageScore"]["num"]/count($studentExam)));
        $output["alycard"]['averageScore']["trend"] = 0;
        if (count($studentExam) > 1) {
            $output["alycard"]['averageScore']["trend"] = floatval(sprintf("%.2f", $last["score"] - ($before["score"] / count($studentExam) - 1) / $output["alycard"]["averageScore"]["num"]));
        }

        // 正确率
        $output["alycard"]["overallAccuracy"]["num"] = floatval(sprintf("%.2f", $output["alycard"]["overallAccuracy"]["num"]/count($studentExam)));
        
        $output["alycard"]['overallAccuracy']["trend"] = 0;
        if (count($studentExam) > 1) {
            $output["alycard"]['overallAccuracy']["trend"] = floatval(sprintf("%.2f", $last["correct"] - ($before["correct"] / count($studentExam) - 1) / $output["alycard"]["overallAccuracy"]["num"]));
        }

        $output["alycard"]["totalErrors"]["num"]  = floatval(sprintf("%.2f", $output["alycard"]["totalErrors"]["num"]/count($studentExam)));
        $output["alycard"]['totalErrors']["trend"] = 0;
        if (count($studentExam) > 1) {
            $output["alycard"]['totalErrors']["trend"] = floatval(sprintf("%.2f", $last["errcount"] - ($before["errcount"] / count($studentExam) - 1) / $output["alycard"]["totalErrors"]["num"]));
        }
        
        // 用错误qids 去找errortags
        $serviceQt = new Service_Data_Questiontag();
        $conds = array(
            sprintf("qid in (%s)", implode(",", Zy_Helper_Utils::arrayInt($errTagQidsArr)))
        );
        $qtMap = $serviceQt->getTagByQids($conds, true);
        
        $totalErrTagCnt = 0;
        foreach ($errTagQidsArr as $qid) {
            if (!empty($qtMap[$qid])) {
                foreach ($qtMap[$qid] as $tag) {
                    if (!isset($output["alyerrtags"][$tag["id"]])) {
                        $output["alyerrtags"][$tag["id"]] = array(
                            "name" => $tag["title"],
                            "description" => $tag["description"],
                            "value" => 0,
                        );
                        $totalErrTagCnt ++;
                    }
                    $output["alyerrtags"][$tag["id"]]['value'] ++;
                }
            }
        }
        $output["alyerrtags"] = array_values($output["alyerrtags"]);
        foreach ($output["alyerrtags"] as &$v) {
            $v["rate"] = sprintf("%.2f", ($v["value"] / $totalErrTagCnt * 100));
        }

        return $output;
    }
}