<?php
class Controller_Mock extends Zy_Core_Controller{

    public $actions = array(
        // 考题
        "question_create"       => "actions/mock/question/Create.php",
        "question_createimport" => "actions/mock/question/Createimport.php",
        "question_createbatch"  => "actions/mock/question/Createbatch.php",
        "question_lists"        => "actions/mock/question/Lists.php",
        "question_delete"       => "actions/mock/question/delete.php",  
        "question_detail"       => "actions/mock/question/detail.php",  
        
        // 试卷
        "paper_lists"        => "actions/mock/paper/Lists.php",
        "paper_create"       => "actions/mock/paper/Create.php",
        "paper_delete"       => "actions/mock/paper/Delete.php",
        "paper_update"       => "actions/mock/paper/Update.php",
        "paper_detail"       => "actions/mock/paper/Detail.php",
        "paper_addquestion"  => "actions/mock/paper/Addquestion.php",
        "paper_updatequestion"  => "actions/mock/paper/Updatequestion.php",

        // 标签
        "tag_create"       => "actions/mock/tag/Create.php",
        "tag_lists"        => "actions/mock/tag/Lists.php",
        "tag_update"       => "actions/mock/tag/Update.php",
        "tag_delete"       => "actions/mock/tag/Delete.php",  
                
        // 来源
        "source_create"       => "actions/mock/source/Create.php",
        "source_lists"        => "actions/mock/source/Lists.php",
        "source_update"       => "actions/mock/source/Update.php",
        "source_delete"       => "actions/mock/source/Delete.php",  

        // 考试
        "exam_start"        => "actions/mock/exam/Start.php",
        "exam_end"          => "actions/mock/exam/End.php",
        "exam_lists"        => "actions/mock/exam/Lists.php",
        "exam_update"       => "actions/mock/exam/Update.php",
        "exam_delete"       => "actions/mock/exam/Delete.php",          
        "exam_detail"       => "actions/mock/exam/Detail.php", 
        "exam_monitor"      => "actions/mock/exam/Monitor.php", 
        "exam_student"      => "actions/mock/exam/Student.php", 
        
    );
}
