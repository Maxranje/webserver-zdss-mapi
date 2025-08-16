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
                        "schemaApi"=>"get:/public/mis/mock/dashboard.json"
                    ),
                ),
            ),
        ),
    ),
    "menu" => array(
        array(
            "id" => 5010,
            "label"=>"题库",
            "url"=>"/paper",
            "icon"=>"fa fa-newspaper-o",
            "children"=>[
                array(
                    "id" => 5011,
                    "label"=>"试卷列表",
                    "url"=>"/paper/list",
                    "icon"=>"fa fa-list-ul",
                    "schemaApi"=>"get:/public/mis/mock/crud-paper-list.json"
                ),         
                array(
                    "id" => 5012,
                    "label"=>"考题详情",
                    "url"=>"/paper/details",
                    "visible" => false,
                    "schemaApi"=>"get:/public/mis/mock/form-paper.json"
                ),       
                array(
                    "id" => 5013,
                    "label"=>"来源",
                    "url"=>"/paper/source",
                    "icon"=>"fa fa-archive",
                    "schemaApi"=>"get:/public/mis/mock/crud-source-list.json"
                ),
                array(
                    "id" => 5015,
                    "label"=>"科目",
                    "url"=>"/paper/subject",
                    "icon"=>"fa fa-server",
                    "schemaApi"=>"get:/public/mis/pages/crud-subject-list.json"
                ), 
            ],
        ),         
        array(
            "id" => 5020,
            "label"=>"模考",
            "url"=>"/mock",
            "icon"=>"fa fa-pencil",
            "children"=>[
                array(
                    "id" => 5021,
                    "label"=>"模考记录",
                    "url"=>"/mock/records",
                    "icon"=>"fa fa-list",
                    "schemaApi"=>"get:/public/mis/mock/crud-mock-list.json"
                ), 
                array(
                    "id" => 5022,
                    "label"=>"开始模考",
                    "url"=>"/mock/start",
                    "icon"=>"fa fa-arrow-circle-right",
                    "schemaApi"=>"get:/public/mis/mock/form-mock.json"
                ),                 
            ]
        ),        
        array(
            "id" => 5030,
            "label"=>"标签库",
            "url"=>"/tags",
            "icon"=>"fa fa-tags",
            "children"=>[
                array(
                    "id" => 5031,
                    "label"=>"标签树",
                    "url"=>"/tags/tree",
                    "icon"=>"fa fa-tree",
                    "schemaApi"=>"get:/public/mis/mock/form-tags-charts.json"
                ),
                array(
                    "id" => 5032,
                    "label"=>"标签列表",
                    "url"=>"/tags/list",
                    "icon"=>"fa fa-list",
                    "schemaApi"=>"get:/public/mis/mock/crud-tags-list.json"
                ),                
            ],
        ),      
        array(
            "id" => 5040,
            "label"=>"错题本",
            "url"=>"/wrong",
            "icon"=>"fa fa-book",
            "children"=>[
                array(
                    "id" => 5041,
                    "label"=>"学员试题",
                    "url"=>"/wrong/question",
                    "icon"=>"fa fa-list-alt",
                    "schemaApi"=>"get:/public/mis/mock/crud-wrong-question-list.json"
                ),
                array(
                    "id" => 5042,
                    "label"=>"模考试卷",
                    "url"=>"/wrong/paper",
                    "icon"=>"fa fa-list",
                    "schemaApi"=>"get:/public/mis/mock/crud-wrong-paper-list.json"
                ),                
            ],
        ),            
        array(
            "id" => 5050,
            "label"=>"单词量",
            "url"=>"/word",
            "icon"=>"fa fa-cubes",
            "children"=>[
                array(
                    "id" => 5051,
                    "label"=>"标签树",
                    "url"=>"/tag/list",
                    "icon"=>"fa fa-tree",
                    "schemaApi"=>"get:/public/mis/pages/crud-mock-question-list.json"
                ),
                array(
                    "id" => 5052,
                    "label"=>"标签管理",
                    "url"=>"/tag/list",
                    "icon"=>"fa fa-tree",
                    "schemaApi"=>"get:/public/mis/pages/crud-mock-question-list.json"
                ),                
            ],
        ),               
    ),
);