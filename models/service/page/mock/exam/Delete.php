<?php

class Service_Page_Mock_Exam_Delete extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin() && !$this->isModeAble(Service_Data_Roles::ROLE_MODE_MOCK_DONE)) {
            throw new Zy_Core_Exception(405, "无权限操作");
        }

        $examId = empty($this->request['exam_id']) ? 0 : intval($this->request['exam_id']);
        if ($examId <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 未选定模考");
        }

        $serviceExam = new Service_Data_Exam();
        $examInfo = $serviceExam->getExamById($examId);
        if (empty($examInfo) || $examInfo["status"] != Service_Data_Exam::EXAM_STATUS_PENDING) {
            throw new Zy_Core_Exception(405, "操作失败, 模考不存在或已在进行中, 不允许删除");
        }

        $ret = $serviceExam->delete($examId, intval($examInfo["pid"]));
        if ($ret == false) {
            throw new Zy_Core_Exception(405, "删除错误, 请重试");
        }
        return array();
    }
}