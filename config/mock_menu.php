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
            "label"=>"考生管理",
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
                    "label"=>"新增题目",
                    "url"=>"/question/add",
                    "visible" => false,
                    "schemaApi"=>"get:/public/mis/mock/form-question-add.json"
                ),                
                array(
                    "id" => 10013,
                    "label"=>"复制题目",
                    "url"=>"/question/copy",
                    "visible" => false,
                    "schemaApi"=>"get:/public/mis/mock/form-question-copy.json"
                ),
                array(
                    "id" => 10014,
                    "label"=>"来源配置",
                    "url"=>"/question/source",
                    "icon"=>"fa fa-mail-forward",
                    "schemaApi"=>"get:/public/mis/mock/crud-source-list.json"
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
            "icon"=>"fa fa-file-text",
            "schemaApi"=>"get:/public/mis/mock/crud-paper-list.json"
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
                    "label"=>"编辑考试",
                    "schemaApi"=>"get:/public/mis/mock/form-exam-edit.json"
                ),                
                array(
                    "id" => 10043,
                    "label"=>"考试列表",
                    "url"=>"/exam/list",
                    "icon"=>"fa fa-history",
                    "schemaApi"=>"get:/public/mis/mock/crud-exam-list.json"
                ),
                array(
                    "id" => 10044,
                    "label"=>"模考监控",
                    "url"=>"/exam/monitor",
                    "icon"=>"fa fa-retweet",
                    "visible" => false,
                    "schemaApi"=>"get:/public/mis/mock/form-exam-monitor.json"
                ),                
                array(
                    "id" => 10045,
                    "label"=>"批改试卷",
                    "url"=>"/exam/reviewlists",
                    "icon"=>"fa fa-retweet",
                    "schemaApi"=>"get:/public/mis/mock/crud-exam-review.json"
                ),
                array(
                    "id" => 10046,
                    "label"=>"试卷详情",
                    "url"=>"/exam/review",
                    "visible" => false,
                    "schemaApi"=>"get:/public/mis/mock/form-exam-review.json"
                ),                
            ],
        ),
        array(
            "id" => 10050,
            "label"=>"错题本",
            "url"=>"/wrong/student",
            "icon"=>"fa fa-book",
            "visible" => false,
            "schemaApi"=>"get:/public/mis/mock/form-wrong-student-detail.json"
        )     
    ),
);