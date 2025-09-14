<?php
defined('BASEPATH') OR exit('No direct script access allowed');
return array(
    "head" => array(
        'pages' => array(
            array(
                'label' => "Home",
                'url' => '/',
                "redirect" => "/index/1"
            ),
            array(
                'children' => array(
                    array(
                        "id" => 10000,
                        "label" =>  "Dashboard",
                        "url"=>"/index/1",
                        "icon"=>"fa fa-home",
                        "schemaApi"=>"get:/public/mis/mock/dashboard.json"
                    ),
                ),
            ),
        ),
    ),
    "menu" => array(
        array(
            "id" => 10001,
            "label"=>"学员管理",
            "url"=>"student",
            "icon"=>"fa fa-group",
            "schemaApi"=>"get:/public/mis/mock/crud-student-list.json"
        ),
        array(
            "id" => 10010,
            "label"=>"题库管理",
            "url"=>"/question",
            "icon"=>"fa fa-newspaper-o",
            "children"=>[
                array(
                    "id" => 10011,
                    "label"=>"系统题库",
                    "url"=>"/question/list",
                    "icon"=>"fa fa-list",
                    "schemaApi"=>"get:/public/mis/mock/crud-question-list.json"
                ),
                array(
                    "id" => 10012,
                    "url"=>"/question/add",
                    "visible" => false,
                    "schemaApi"=>"get:/public/mis/mock/form-question-add.json"
                ),                
                array(
                    "id" => 10013,
                    "url"=>"/question/copy",
                    "visible" => false,
                    "schemaApi"=>"get:/public/mis/mock/form-question-copy.json"
                ),
                array(
                    "id" => 10014,
                    "label"=>"单词题库",
                    "url"=>"/question/word",
                    "icon"=>"fa fa-list-alt",
                    "schemaApi"=>"get:/public/mis/mock/crud-question-word-list.json"
                ), 
                array(
                    "id" => 10015,
                    "url"=>"/question/word/import",
                    "visible" => false,
                    "schemaApi"=>"get:/public/mis/mock/form-question-word.json"
                ),
            ],
        ),
        array(
            "id" => 10020,
            "label"=>"标签库",
            "url"=>"/tag",
            "icon"=>"fa fa-tags",
            "schemaApi"=>"get:/public/mis/mock/crud-tag-list.json"
        ),
        array(
            "id" => 10030,
            "label"=>"试卷管理",
            "url"=>"/paper",
            "icon"=>"fa fa-file",
            "children"=>[
                array(
                    "id" => 10021,
                    "label"=>"试卷列表",
                    "url"=>"/paper/list",
                    "icon"=>"fa fa-file-text",
                    "schemaApi"=>"get:/public/mis/mock/crud-paper-list.json"
                ),                             
                array(
                    "id" => 10022,
                    "label"=>"来源配置",
                    "url"=>"/paper/source",
                    "icon"=>"fa fa-mail-forward",
                    "schemaApi"=>"get:/public/mis/mock/crud-source-list.json"
                ),
            ],
        ),
        array(
            "id" => 10030,
            "label"=>"科目管理",
            "url"=>"/paper/subject",
            "icon"=>"fa fa-server",
            "schemaApi"=>"get:/public/mis/pages/crud-subject-list.json"
        ),
        array(
            "id" => 10040,
            "label"=>"模考中心",
            "url"=>"/exam",
            "icon"=>"fa fa-desktop",
            "children"=>[
                array(
                    "id" => 10041,
                    "label"=>"开始考试",
                    "url"=>"/exam/start",
                    "icon"=>"fa fa-bullseye",
                    "schemaApi"=>"get:/public/mis/mock/form-exam-start.json"
                ),
                array(
                    "id" => 10042,
                    "url"=>"/exam/edit",
                    "visible" => false,
                    "schemaApi"=>"get:/public/mis/mock/form-exam-edit.json"
                ),                
                array(
                    "id" => 10043,
                    "label"=>"考试记录",
                    "url"=>"/exam/list",
                    "icon"=>"fa fa-history",
                    "schemaApi"=>"get:/public/mis/mock/crud-exam-list.json"
                ),
                array(
                    "id" => 10045,
                    "label"=>"模考监控",
                    "url"=>"/exam/monitor",
                    "icon"=>"fa fa-retweet",
                    "schemaApi"=>"get:/public/mis/mock/form-exam-monitor.json"
                ),
            ],
        ),
        array(
            "id" => 10050,
            "label"=>"错题本",
            "url"=>"/wrong",
            "icon"=>"fa fa-book",
            "children"=>[
                array(
                    "id" => 10051,
                    "label"=>"考题记录",
                    "url"=>"/wrong/question",
                    "icon"=>"fa fa-newspaper-o",
                    "schemaApi"=>"get:/public/mis/mock/crud-wrong-question-list.json"
                ),
                array(
                    "id" => 10052,
                    "label"=>"学员记录",
                    "url"=>"/wrong/student",
                    "icon"=>"fa fa-group",
                    "schemaApi"=>"get:/public/mis/mock/crud-wrong-student-list.json"
                )                
            ],
        ),
    ),
);