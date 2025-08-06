<?php
class Controller_Napi extends Zy_Core_Controller{

    public $actions = array(
        "abroadplan_summary"    => "actions/napi/abroadplan/Summary.php",
        "abroadplan_lists"      => "actions/napi/abroadplan/Lists.php",
        "abroadplan_check"      => "actions/napi/abroadplan/Check.php",

        // 日历相关
        "calendar_student"     => "actions/napi/calendar/Student.php",
        "calendar_typelists"   => "actions/napi/calendar/Typelists.php",
        "calendar_platform"    => "actions/napi/calendar/Platform.php",
        "calendar_teacher"     => "actions/napi/calendar/Teacher.php",

        // 课程
        "schedule_summary"    => "actions/napi/schedule/Summary.php",
        "schedule_tsummary"   => "actions/napi/schedule/Tsummary.php",        
    );
}
