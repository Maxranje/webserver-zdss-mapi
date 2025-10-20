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

        $startEnd = Zy_Helper_Utils::arrayInt(explode(",", $startEnd));
        if (count($startEnd) != 2 || $startEnd[0] <=0 || $startEnd[1] <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 日期时间范围选定不正确");
        }
        $startTime  = $startEnd[0];
        $endTime    = $startEnd[1];
        if ($endTime <= $startTime ) {
            throw new Zy_Core_Exception(405, "操作失败, 日期时间范围选定不正确!");
        }

        if ($expireTime < 10 || $expireTime > 240) {
            throw new Zy_Core_Exception(405, "操作失败, 时长必须要在10分钟到240分钟之间");
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
        $qtMap = $serviceData->getQidByTagAndLevel($tagIds, $level, $questionCnt);
        if (empty($qtMap) || count($qtMap) <= 5) {
            throw new Zy_Core_Exception(405, "操作失败, 搜寻到题目数小于5道, 不足以支持一次考试");
        }
        $qids = Zy_Helper_Utils::arrayInt($qtMap, "qid");

        $serviceData = new Service_Data_Question();
        $questions = $serviceData->getQuestionByIds($qids, true);
        if (empty($questions)) {
            throw new Zy_Core_Exception(405, "操作失败, 获取考题信息失败");
        }
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
            "total_question"    => count($qtMap),
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
}