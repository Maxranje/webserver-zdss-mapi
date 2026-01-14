<?php

class Service_Page_Napi_Exam_Upload extends Service_Page_Napi_Exam_Service{

    public function execute () {
        if (!$this->checkMockStudent()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $uid        = $this->adption["userid"];
        $qid        = empty($this->request['qid']) ? 0 : intval($this->request['qid']);
        $examId     = empty($this->request['examid']) ? 0 : intval($this->request['examid']);
        
        if ($qid <= 0 || $examId <= 0) {
            throw new Zy_Core_Exception(405, "试题不匹配, 请重新上传或刷新后重新上传");
        }
        $this->studentExamCheck($examId, $uid);

        // 获取试题信息
        $serviceQuestion = new Service_Data_Question();
        $currentQuestion = $serviceQuestion->getQuestionById($qid);
        if (empty($currentQuestion)) {
            Zy_Helper_Log::addnotice(sprintf("uid:%d, examid:%d, exam_upload qid empty, qid:%d", $uid, $examId, $qid));
            throw new Zy_Core_Exception(405, "试题不匹配, 请重新上传或刷新后重新上传");
        }
        if ($currentQuestion["type"] != Service_Data_Question::QUESTION_TYPE_SPEAK) {
            Zy_Helper_Log::addnotice(sprintf("uid:%d, examid:%d, exam_upload qid not speak, qid:%d", $uid, $examId, $qid));
            throw new Zy_Core_Exception(405, "试题不匹配, 当前试题不是音频试题, 请联系监考老师");
        }

        $uploadPath = Zy_Helper_Config::getConfig('config')['upload_path'] . "/mock_speak";
        $uploadKey = sprintf("%s_%s_%s", $uid, $examId, $qid);

        try{
            $uploadRet = Zy_Helper_Upload::saveUploadedMockSpeakFile($uploadPath, $uploadKey);
        }catch (Exception $e) {
            Zy_Helper_Log::warning($uploadKey . " " .$e->getMessage());
            throw new Exception("上传失败");
        }

        return array(
            "audioPath" => $uploadRet,
        );
    }
}