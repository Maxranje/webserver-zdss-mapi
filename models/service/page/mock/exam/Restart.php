<?php

class Service_Page_Mock_Exam_Restart extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $expireTime  = empty($this->request['expire_time']) ? 0 : intval($this->request['expire_time']);
        $startEnd    = empty($this->request['start_end']) ? "" : trim($this->request['start_end']);
        $remark      = empty($this->request['remark']) ? "" : trim($this->request['remark']);
        $studentUid  = empty($this->request['student_uid']) ? 0 : intval($this->request['student_uid']);
        $tagIds      = empty($this->request['reward_tag_ids']) ? array() : Zy_Helper_Utils::arrayInt(explode(",", $this->request['reward_tag_ids']));
        $level       = empty($this->request['level']) ? array() : Zy_Helper_Utils::arrayInt(explode(",", $this->request['level']));
        $teacherUid  = OPERATOR;
        $questionCnt = 30;

        if ($studentUid <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 选定学员失败");
        }

        if (empty($tagIds)) {
            throw new Zy_Core_Exception(405, "操作失败, 没有标签信息");
        }

        if (!empty($level) && array_diff($level, Service_Data_Question::QUESTION_LEVEL_MAP)) {
            throw new Zy_Core_Exception(405, "操作失败, 难度信息参数异常");
        }
        
        if ($expireTime < 10 || $expireTime > 240) {
            throw new Zy_Core_Exception(405, "操作失败, 时长必须要在10分钟到240分钟之间");
        }        

        $startEnd = Zy_Helper_Utils::arrayInt(explode(",", $startEnd));
        if (count($startEnd) != 2 || $startEnd[0] <=0 || $startEnd[1] <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 日期时间范围选定不正确");
        }
        $startTime  = $startEnd[0];
        $endTime    = $startEnd[1];
        if ($endTime <= $startTime ) {
            throw new Zy_Core_Exception(405, "操作失败, 日期时间范围选定不正确!");
        }
        if ($endTime - $startTime > 7*86400) {
            throw new Zy_Core_Exception(405, "操作失败, 允许考试周期时间范围必须7天以内");
        }
        if ($endTime - $startTime <= $expireTime * 60) {
            throw new Zy_Core_Exception(405, "操作失败, 允许考试周期时间范围必须大于考试时长");
        }        

        if (!empty($remark) && !Zy_Helper_Utils::validateString($remark, 1, 100)) {
            throw new Zy_Core_Exception(405, "操作失败, 备注限定100字内");
        }

        // 考生信息
        $serviceData = new Service_Data_Profile();
        $userInfo = $serviceData->getUserInfoByUid($studentUid);
        if (empty($userInfo) || 
            $userInfo["type"] != Service_Data_Profile::USER_TYPE_STUDENT || 
            $userInfo["is_mock"] != Service_Data_Profile::STUDENT_MOCK) {
            throw new Zy_Core_Exception(405, "操作失败, 考生信息不存在或非模考类型用户");
        }

        // 根据tags和level, 找到30道题, 最少得5道题
        $serviceData = new Service_Data_QuestionTag();
        $conds = array(
            sprintf("tag_id in (%s)", implode(",",$tagIds)),
        );
        $appends = array(
            "limit " .  (empty($level) ? 300 : 200),
        );
        $tagQuestions = $serviceData->getListByConds($conds, array(), null, $appends);
        $tagQuestions = $this->selectQuestions($tagQuestions, $questionCnt, $level);
        if (empty($tagQuestions) || count($tagQuestions) <= 5) {
            throw new Zy_Core_Exception(405, "操作失败, 搜寻到题目数小于5道, 不足以支持一次考试");
        }
        $qids = Zy_Helper_Utils::arrayInt($tagQuestions, "qid");

        //get question infos
        $serviceData = new Service_Data_Question();
        $questions = $serviceData->getQuestionByIds($qids, true);
        if (empty($questions)) {
            throw new Zy_Core_Exception(405, "操作失败, 获取考题信息失败");
        }
        usort($questions, function($a, $b) {
            return $a["parent_id"] - $b["parent_id"] > 0 ? 1 : -1;
        });

        $totalScore = $totalQuestion = 0;
        foreach ($questions as $v) {
            $totalScore += $v["score"];
            $totalQuestion++;
        }

        $paperType = Service_Data_Paper::PAPER_TYPE_NORMAL;

        $serviceData = new Service_Data_Exam();
        $profile = [
            "indentify"         => Zy_Helper_Utils::autoID("EX"),
            "start_time"        => $startTime,
            "end_time"          => $endTime,
            "expire_time"       => $expireTime,
            "total_question"    => count($tagQuestions),
            "student_uids"      => array($studentUid),
            "teacher_uid"       => $teacherUid,
            "remark"            => $remark,
            'paper'             => array(
                "type" => $paperType,
                "questions" => $questions,
                "total_score" => $totalScore,
                "total_question" => $totalQuestion,
            ),
            "userInfo" => $userInfo,
        ];

        $ret = $serviceData->createReward($profile);
        if ($ret == false) {
            throw new Zy_Core_Exception(405, "配置考试失败, 请重试");
        }
        return array();
    }

    // 规则
    function selectQuestions($questions, $cnt = 30, $levels = []) {
        if ($cnt <= 0 || empty($questions)) {
            return [];
        }
        shuffle($questions);
        
        // 按level分组
        $grouped = [];
        foreach ($questions as $q) {
            $grouped[$q['level']][] = $q;
        }
        
        $result = [];
        
        if (!empty($levels)) {
            // 指定levels：均衡分配
            $levelCount = count($levels);
            $baseCount = floor($cnt / $levelCount);
            $remainder = $cnt % $levelCount;
            
            foreach ($levels as $level) {
                if (!isset($grouped[$level])) continue;
                
                $needCount = $baseCount + ($remainder > 0 ? 1 : 0);
                $remainder = max(0, $remainder - 1);
                
                $available = $grouped[$level];
                $takeCount = min($needCount, count($available));
                $result = array_merge($result, array_slice($available, 0, $takeCount));
            }
        } else {
            // 默认比例：1,4,5各10%，2占40%，3占30%
            $distribution = [
                2 => floor($cnt * 0.4),  // 40%
                3 => floor($cnt * 0.3),  // 30%
                1 => floor($cnt * 0.1),  // 10%
                4 => floor($cnt * 0.1),  // 10%
                5 => floor($cnt * 0.1)   // 10%
            ];
            
            // 优先分配level2和level3
            foreach ([2, 3, 1, 4, 5] as $level) {
                if (!isset($grouped[$level])) continue;
                
                $needCount = $distribution[$level];
                $available = $grouped[$level];
                $takeCount = min($needCount, count($available));
                
                if ($takeCount > 0) {
                    $result = array_merge($result, array_slice($available, 0, $takeCount));
                }
            }
        }
        
        return $result;
    }
}