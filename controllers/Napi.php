<?php
class Controller_Napi extends Zy_Core_Controller{

    public $actions = array(
        "abroadplan_summary"    => "actions/napi/abroadplan/Summary.php",
        "abroadplan_lists"      => "actions/napi/abroadplan/Lists.php",
        "abroadplan_check"      => "actions/napi/abroadplan/Check.php",
        "abroadplan_down"       => "actions/napi/abroadplan/Down.php",

        // 日历相关
        "calendar_student"     => "actions/napi/calendar/Student.php",
        "calendar_typelists"   => "actions/napi/calendar/Typelists.php",
        "calendar_platform"    => "actions/napi/calendar/Platform.php",
        "calendar_teacher"     => "actions/napi/calendar/Teacher.php",

        // 课程
        "schedule_summary"    => "actions/napi/schedule/Summary.php",
        "schedule_tsummary"   => "actions/napi/schedule/Tsummary.php",     
        
        // 考试
        "exam_detail"   => "actions/napi/exam/Detail.php",
        "exam_summary"  => "actions/napi/exam/Summary.php",
        "exam_lists"    => "actions/napi/exam/Lists.php",     
        "exam_init"     => "actions/napi/exam/Init.php",
        "exam_get"      => "actions/napi/exam/Get.php",
        "exam_save"     => "actions/napi/exam/Save.php",
        "exam_upload"   => "actions/napi/exam/Upload.php",
        "exam_submit"   => "actions/napi/exam/Submit.php",
        "exam_alydata"  => "actions/napi/exam/Alydata.php",  
    );
}
