<?php

class Service_Page_Napi_Exam_Summary extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkStudent()) {
            throw new Zy_Core_Exception(405, "无权限");
        }
        $uid = $this->adption["userid"]; 
        $result = array(
            'total_exams'   => 0,
            'words_level'   => "level 2",
        );

        $serviceExam = new Service_Data_Exam();
        
        $result["total_exams"] =  $serviceExam->getExamCntBySuid($uid);
        return $result;
    }
}