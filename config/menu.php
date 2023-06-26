<?php
defined('BASEPATH') OR exit('No direct script access allowed');
return array(
    "defualt_teacher" => array(
        
    ),
    "defualt_amdins" => array(
        1,2,3,41,42,101,102,51,72,
    ),
    "menu" => array(
        array(
            "id" => 2,
            "label"=>"学员管理",
            "url"=>"/student",
            "icon"=>"fa fa-bars",
            "children"=>[
                array(
                    "id" => 21,
                    "label"=>"学员列表",
                    "url"=>"/student/list",
                    "icon"=>"fa fa-list",
                    "schemaApi"=>"get:/public/pages/crud-student-list.json"
                ),
                array(
                    "id" => 22,
                    "label"=>"订单列表",
                    "url"=>"/student/orderlist",
                    "icon"=>"fa fa-bars",
                    "schemaApi"=>"get:/public/pages/crud-student-orders.json"
                )
            ]
        ),
        array(
            "id" => 3,
            "label"=>"班级管理",
            "url"=>"/group",
            "icon"=>"fa fa-envelope-o",
            "schemaApi"=>"get:/public/pages/crud-group-list.json"
        ),
        array(
            "id" => 11,
            "label"=>"个人课表",
            "url"=>"/calendar",
            "icon"=>"fa fa-calendar",
            "link"=>"http://zdss.cn/mapi/dashboard/home"
        ),
        array(
            "id" => 4,
            "label"=>"排课管理",
            "url"=>"/schedule",
            "icon"=>"fa fa-plus-square-o",
            "children"=>[
                array(
                    "id" => 41,
                    "label"=>"开始排课",
                    "url"=>"/schedule/schedulestart",
                    "icon"=>"fa fa-pencil-square-o",
                    "schemaApi"=>"get:/public/pages/form-schedule.json"
                ),
                array(
                    "id" => 42,
                    "label"=>"排课列表",
                    "url"=>"/schedule/schedulelist",
                    "icon"=>"fa fa-list",
                    "schemaApi"=>"get:/public/pages/crud-schedule-list.json"
                )
            ]
        ),
        array(
            "id" => 10,
            "label"=>"教室管理",
            "url"=>"/arearoom",
            "icon"=>"fa fa-archive",
            "children"=>[
                array(
                    "id" => 101,
                    "label"=>"教室预览",
                    "url"=>"/schedule/arearoute",
                    "icon"=>"fa fa-calendar",
                    "schemaApi"=>"get:/public/pages/crud-area-route.json"
                ),
                array(
                    "id" => 102,
                    "label"=>"配置教室",
                    "url"=>"/schedule/pkarealist",
                    "icon"=>"fa fa-bars",
                    "schemaApi"=>"get:/public/pages/crud-pkarea-list.json"
                )
            ]
        ),
        array(
            "id" => 5,
            "label"=>"统计管理",
            "url"=>"/statistics",
            "icon"=>"fa fa-bar-chart-o",
            "children"=>[
                array(
                    "id" => 51,
                    "label"=>"订单记录",
                    "url"=>"/statistics/lists",
                    "icon"=>"fa fa-bars",
                    "schemaApi"=>"get:/public/pages/crud-statistics-list.json"
                ),
                array(
                    "id" => 52,
                    "label"=>"学班记录",
                    "url"=>"/statistics/detaillists",
                    "icon"=>"fa fa-list",
                    "schemaApi"=>"get:/public/pages/crud-statistics-details.json"
                )
            ]
        ),
        array(
            "id" => 6,
            "label"=>"科目分类",
            "url"=>"/subject",
            "icon"=>"fa fa-server",
            "schemaApi"=>"get:/public/pages/crud-subject-list.json"
        ),
        array(
            "id" => 7,
            "label"=>"教师管理",
            "url"=>"/teacher",
            "icon"=>"fa fa-credit-card",
            "children"=>[
                array(
                    "id" => 71,
                    "label"=>"教师列表",
                    "url"=>"/teacher/lists",
                    "icon"=>"fa fa-list",
                    "schemaApi"=>"get:/public/pages/crud-teacher-list.json"
                ),
                array(
                    "id" => 72,
                    "label"=>"教师锁定",
                    "url"=>"/teacher/lock",
                    "icon"=>"fa fa-bars",
                    "schemaApi"=>"get:/public/pages/crud-teacherlock-list.json"
                ),
            ]
        ),
        array(
            "id" => 8,
            "label"=>"校区管理",
            "url"=>"/area",
            "icon"=>"fa fa-street-view",
            "schemaApi"=>"get:/public/pages/crud-area-list.json"
        ),
        array(
            "id" => 9,
            "label"=>"系统信息",
            "url"=>"/system",
            "icon"=>"fa fa-cog",
            "children"=>[
                array(
                    "id" => 91,
                    "label"=>"管理员",
                    "url"=>"/system/admins",
                    "icon"=>"fa fa-list",
                    "schemaApi"=>"get:/public/pages/crud-admin-list.json"
                ),
                array(
                    "id" => 92,
                    "label"=>"角色配置",
                    "url"=>"/system/roles",
                    "icon"=>"fa fa-bars",
                    "schemaApi"=>"get:/public/pages/crud-roles-list.json"
                )
            ]
        )
    )
);