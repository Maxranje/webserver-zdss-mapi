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
                        "id" => 5000,
                        "label" =>  "Dashboard",
                        "url"=>"/index/1",
                        "icon"=>"fa fa-home",
                        "schemaApi"=>"get:/public/sdk/mock/dashboard.json"
                    ),
                ),
            ),
        ),
    ),
    "menu" => array(
        array(
            "id" => 5010,
            "label"=>"题库管理",
            "url"=>"/question",
            "icon"=>"fa fa-pencil",
            "children"=>[
                array(
                    "id" => 5011,
                    "label"=>"题库列表",
                    "url"=>"/question/list",
                    "icon"=>"fa fa-list-ul",
                    "schemaApi"=>"get:/public/sdk/mock/crud-question-list.json"
                ),
            ],
        ),
        array(
            "id" => 5020,
            "label"=>"试卷管理",
            "url"=>"/paper",
            "icon"=>"fa fa-newspaper-o",
            "schemaApi"=>"get:/public/sdk/mock/crud-paper-list.json"
        ),
        array(
            "id" => 5030,
            "label"=>"开始考试",
            "url"=>"/ctreator",
            "icon"=>"fa fa-pencil",
            "schemaApi"=>"get:/public/sdk/mock/form-create-mock.json"
        ),        
        array(
            "id" => 5040,
            "label"=>"标签库",
            "url"=>"/tags",
            "icon"=>"fa fa-tags",
            "children"=>[
                array(
                    "id" => 5041,
                    "label"=>"标签树",
                    "url"=>"/tags/tree",
                    "icon"=>"fa fa-tree",
                    "schemaApi"=>"get:/public/sdk/mock/form-tags-charts.json"
                ),
                array(
                    "id" => 5042,
                    "label"=>"标签列表",
                    "url"=>"/tags/list",
                    "icon"=>"fa fa-list",
                    "schemaApi"=>"get:/public/sdk/mock/crud-tags-list.json"
                ),                
            ],
        ),      
        array(
            "id" => 5050,
            "label"=>"错题本",
            "url"=>"/wrong",
            "icon"=>"fa fa-book",
            "children"=>[
                array(
                    "id" => 5051,
                    "label"=>"学员试题",
                    "url"=>"/wrong/question",
                    "icon"=>"fa fa-list-alt",
                    "schemaApi"=>"get:/public/sdk/mock/crud-wrong-question-list.json"
                ),
                array(
                    "id" => 5052,
                    "label"=>"模考试卷",
                    "url"=>"/wrong/paper",
                    "icon"=>"fa fa-list",
                    "schemaApi"=>"get:/public/sdk/mock/crud-wrong-paper-list.json"
                ),                
            ],
        ),            
        array(
            "id" => 5060,
            "label"=>"单词量",
            "url"=>"/word",
            "icon"=>"fa fa-cubes",
            "children"=>[
                array(
                    "id" => 5061,
                    "label"=>"标签树",
                    "url"=>"/tag/list",
                    "icon"=>"fa fa-tree",
                    "schemaApi"=>"get:/public/sdk/pages/crud-mock-question-list.json"
                ),
                array(
                    "id" => 5062,
                    "label"=>"标签管理",
                    "url"=>"/tag/list",
                    "icon"=>"fa fa-tree",
                    "schemaApi"=>"get:/public/sdk/pages/crud-mock-question-list.json"
                ),                
            ],
        ),               
    ),
);