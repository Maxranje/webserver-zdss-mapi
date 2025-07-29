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
                        "id" => 7001,
                        "label" =>  "Dashboard",
                        "url"=>"/index/1",
                        "icon"=>"fa fa-home",
                        "schemaApi"=>"get:/public/sdk/pages/dashboard.json"
                    ),
                ),
            ),
        ),
    ),
    "menu" => array(
        array(
            "id" => 2002,
            "label"=>"题库管理",
            "url"=>"/question",
            "icon"=>"fa fa-group",
            "children"=>[
                array(
                    "id" => 121,
                    "label"=>"题库列表",
                    "url"=>"/question/list",
                    "icon"=>"fa fa-list",
                    "schemaApi"=>"get:/public/sdk/pages/crud-mock-question-list.json"
                ),
            ],
        ),
        array(
            "id" => 2002,
            "label"=>"试卷管理",
            "url"=>"/question",
            "icon"=>"fa fa-group",
            "schemaApi"=>"get:/public/sdk/pages/crud-mock-question-list.json"
        ),
    ),
    "mode" => array(
        array(
            "id" => 3001,
            "label"=>"排课编辑",
            "tag" => "排课列表单项编辑能力, 包括上课时间,教师和教室等"
        ),
    ),
);