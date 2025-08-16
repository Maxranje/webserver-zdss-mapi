<?php
class Controller_Mock extends Zy_Core_Controller{

    public $actions = array(
        // 题目
        "question_create"       => "actions/mock/question/Create.php",
        "question_batchcreate"  => "actions/mock/question/Batchcreate.php",
        "question_lists"        => "actions/mock/question/Lists.php",
        "question_update"       => "actions/mock/question/Update.php",
        "question_delete"       => "actions/mock/question/delete.php",  
        
        // 试卷
        "paper_lists"        => "actions/mock/paper/Lists.php",
        "paper_create"       => "actions/mock/paper/Create.php",
        "paper_details"      => "actions/mock/paper/Details.php",

        // 来源
        "source_create"       => "actions/mock/source/Create.php",
        "source_lists"        => "actions/mock/source/Lists.php",
        "source_update"       => "actions/mock/source/Update.php",
        "source_delete"       => "actions/mock/source/delete.php",  
    );
}
