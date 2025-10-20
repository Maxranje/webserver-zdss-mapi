    <?php

class Service_Page_Mock_Wrong_Student extends Zy_Core_Service{
    private $color = array(
        "#DC143C",
        "#FF4500",
        "#FF6347",
        "#FF7F50",
        "#FF8C00",
        "#FFA500",
        "#F0E68C",
        "#EEE8AA",
    );

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $uid    = empty($this->request['student_uid']) ? 0 : intval($this->request['student_uid']);
        $tagIds = empty($this->request['tag_ids']) ? array() : Zy_Helper_Utils::arrayInt(explode(",", $this->request['tag_ids']));
        $range  = 10;
        $isShowQuesiton = !empty($tagIds) ? true : false;

        $serviceProfile = new Service_Data_Profile();
        $student = $serviceProfile->getUserInfoByUid($uid);
        if (empty($student) || $student["is_mock"] != 1) {
            throw new Zy_Core_Exception(405, "操作失败, 用户不存在或没有模考权限");
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
            throw new Zy_Core_Exception(405, "操作失败, 无已结束的模考数据");
        }

        // 拉试卷标题
        $paperInfos = array();
        if (!$isShowQuesiton) {
            $servicePaper = new Service_Data_Paper();
            $paperInfos = $servicePaper->getPaperByIds(Zy_Helper_Utils::arrayInt($studentExam, "pid"));
            if (empty($paperInfos)) {
                throw new Zy_Core_Exception(405, "操作失败, 试卷信息异常");
            }
            $paperInfos = array_column($paperInfos, null, "pid");
        }

        // 拉取所有作答信息
        $conds = array(
            "student_uid" => $uid,
            sprintf("exam_id in (%s)", implode(",", Zy_Helper_Utils::arrayInt($studentExam, "exam_id"))),
        );
        if ($isShowQuesiton) {
            $fileds = (new Dao_ExamAnswer())->arrFieldsMap;
        } else {
            $fileds = (new Dao_ExamAnswer())->simpleFieldsMap;
        }
        $studentAnswers = $serviceExam->getStudentAnswerListByConds($conds, $fileds);
        if (empty($studentAnswers)) {
            throw new Zy_Core_Exception(405, "操作失败, 无已结束的模考数据");
        }

        // 获取答案正确性
        $studentExam = $serviceExam->getMuiltExamAnswerDetail($studentExam, $studentAnswers);

        $alytrendChat = $errTagQidsArr = array();
        foreach ($studentExam as $i => $v) {
            $totalCorrect = 0;
            if (!empty($v["studentAnswerRet"])) {
                foreach ($v["studentAnswerRet"] as $qid => $qv) {
                    $totalCorrect += $qv["is_correct"];
                    if (empty($qv["is_correct"])) {
                        $errTagQidsArr[] = $qid;
                    }
                }
            }

            // 正确率图标
            $alytrendChat["score"][] = $v["score"];
            $alytrendChat["accuracy"][] = empty($v["studentAnswerRet"]) ? 0 : sprintf("%.2f", $totalCorrect / count($v["studentAnswerRet"]) * 100);
            $alytrendChat["labels"][] = empty($paperInfos[$v["pid"]]["title"]) ? date("Ymd H", $v["start_time"]) : $paperInfos[$v["pid"]]["title"];
        }     
        
        // 用错误qids 去找errortags
        $serviceQt = new Service_Data_Questiontag();
        $conds = array(
            sprintf("qid in (%s)", implode(",", Zy_Helper_Utils::arrayInt($errTagQidsArr)))
        );
        $qtMap = $serviceQt->getTagByQids($conds, true);

        if ($isShowQuesiton) {
            return $this->showErrQuestionDetail($qtMap, $tagIds, $studentAnswers);
        }
        
        $totalErr = 0;
        $errTagsArr = array();
        foreach ($errTagQidsArr as $qid) {
            if (!empty($qtMap[$qid])) {
                foreach ($qtMap[$qid] as $tag) {
                    if (!isset($errTagsArr[$tag["id"]])) {
                        $errTagsArr[$tag["id"]] = array(
                            "id" => $tag["id"],
                            "name" => $tag["title"],
                            "description" => $tag["description"],
                            "value" => 0,
                        );
                    }
                    $errTagsArr[$tag["id"]]['value'] ++;
                    $totalErr ++;
                }
            }
        }
        $errTagsArr = array_values($errTagsArr);
        
        $errTagsPie = array();
        foreach ($errTagsArr as &$v) {
            $v["rate"] = sprintf("%.2f", $v["value"] / $totalErr * 100);
            $errTagsPie[] = array("value" => $v["rate"], "name" => $v["name"]);
        }
        usort($errTagsArr, function ($a, $b) {
            return $a["value"] > $b["value"] ? -1 : 1;
        });
        $errTagsArr = array_values(array_slice($errTagsArr, 0, 8));
        foreach ($errTagsArr as $i =>  &$v) {
            $v["index"] = $i+1;
            $v["bgColor"] = $this->color[$i];
        }
        
        return  array(
            "student_name" => $student["nickname"],
            "errTagsPie" => $errTagsPie,
            "alytrendChat" => $alytrendChat,
            "errTagsArr" => $errTagsArr, 
        );
    }

    public function showErrQuestionDetail ($qtMap, $tagIds, $studentAnswers) {
        $qids = array();
        foreach ($qtMap as $qid => $v) {
            if (empty($v)) {
                continue;
            }
            foreach ($v as $item) {
                // 没在请求里过滤
                if (in_array($item["id"], $tagIds)) {
                    $qids[] = $qid;
                }
            }
        }
        if (empty($qids)) {
            return array(
                "type"=> "tpl",
                "className" => "text-black text-md font-bold block mt-2 text-center",
                "tpl"=> "暂未数据"
            );
        }

        $serviceQuestion = new Service_Data_Question();
        $questionData = $serviceQuestion->getQuestionDetails($qids, array(), $studentAnswers);
        if (empty($qid)) {
            return array(
                "type"=> "tpl",
                "className" => "text-black text-md font-bold block mt-2 text-center",
                "tpl"=> "暂未数据"
            );
        }

        $pageReview = new Service_Page_Mock_Exam_Review();
        return $pageReview->formatQuestionAnswerDetail($questionData, true, array());
    }
}