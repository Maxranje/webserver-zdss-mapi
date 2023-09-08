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
                        "id" => 1,
                        "label" =>  "Dashboard",
                        "url"=>"/index/1",
                        "icon"=>"fa fa-home",
                        "schemaApi"=>"get:/public/pages/dashboard.json"
                    ),
                ),
            ),
        ),
    ),
    "menu" => array(
        array(
            "id" => 2,
            "label"=>"学员管理",
            "url"=>"/student",
            "icon"=>"fa fa-group",
            "schemaApi"=>"get:/public/pages/crud-student-list.json"
        ),
        array(
            "id" => 12,
            "label"=>"订单中心",
            "url"=>"/order",
            "icon"=>"fa fa-newspaper-o",
            "children"=>[
                array(
                    "id" => 121,
                    "label"=>"订单列表",
                    "url"=>"/order/list",
                    "icon"=>"fa fa-list",
                    "schemaApi"=>"get:/public/pages/crud-order-list.json"
                ),
                array(
                    "id" => 122,
                    "label"=>"结转记录",
                    "url"=>"/order/transfer",
                    "icon"=>"fa fa-retweet",
                    "schemaApi"=>"get:/public/pages/crud-order-transfer-list.json"
                ),
                array(
                    "id" => 123,
                    "label"=>"退款记录",
                    "url"=>"/order/refund",
                    "icon"=>"fa fa-reply",
                    "schemaApi"=>"get:/public/pages/crud-order-refund-list.json"
                ),
                array(
                    "id" => 124,
                    "label"=>"存额变更",
                    "url"=>"/order/recharge",
                    "icon"=>"fa fa-random",
                    "schemaApi"=>"get:/public/pages/crud-order-recharge-list.json"
                ),
                array(
                    "id" => 125,
                    "label"=>"绑定课程",
                    "url"=>"/order/band",
                    "icon"=>"fa fa-check-square-o",
                    "schemaApi"=>"get:/public/pages/form-order-band.json"
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
                    "schemaApi"=>"get:/public/pages/crud-schedule-area-detail.json"
                ),
                array(
                    "id" => 102,
                    "label"=>"配置教室",
                    "url"=>"/schedule/arealist",
                    "icon"=>"fa fa-bars",
                    "schemaApi"=>"get:/public/pages/crud-schedule-area-list.json"
                )
            ]
        ),
        array(
            "id" => 5,
            "label"=>"统计管理",
            "url"=>"/records",
            "icon"=>"fa fa-bar-chart-o",
            "children"=>[
                array(
                    "id" => 51,
                    "label"=>"结算报表",
                    "url"=>"/records/lists",
                    "icon"=>"fa fa-calendar",
                    "schemaApi"=>"get:/public/pages/crud-records-schedule-list.json"
                ),
                array(
                    "id" => 52,
                    "label"=>"订单报表",
                    "url"=>"/records/order",
                    "icon"=>"fa fa-bars",
                    "schemaApi"=>"get:/public/pages/crud-records-order-list.json"
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
                    "label"=>"锁定时间",
                    "url"=>"/teacher/lock",
                    "icon"=>"fa fa-lock",
                    "schemaApi"=>"get:/public/pages/crud-teacher-lock-list.json"
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
            "isSuper" => 1,
            "children"=>[
                array(
                    "id" => 91,
                    "label"=>"操作人员",
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
                ),
                array(
                    "id" => 93,
                    "label"=>"生源地",
                    "url"=>"/system/birthplace",
                    "icon"=>"fa fa-server",
                    "schemaApi"=>"get:/public/pages/crud-birthplace-list.json"
                ),
            ]
        )
    ),
    "teacher" => array(
        array(
            "id" => 11,
            "label"=>"个人课表",
            "url"=>"/calendar",
            "icon"=>"fa fa-calendar",
            // "link"=>"http://zdss.cn/mapi/dashboard/home"
            "link"=>"http://127.0.0.1:8060/mapi/dashboard/home"
        ),
    ),
);