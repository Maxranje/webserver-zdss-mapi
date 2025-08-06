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
                        "schemaApi"=>"get:/public/mis/pages/dashboard.json"
                    ),
                ),
            ),
        ),
    ),
    "menu" => array(
        array(
            "id" => 2,
            "label"=>"学员管理",
            "url"=>"student",
            "icon"=>"fa fa-group",
            "schemaApi"=>"get:/public/mis/pages/crud-student-list.json"
        ),
        array(
            "id" => 12,
            "label"=>"课程中心",
            "url"=>"/order",
            "icon"=>"fa fa-newspaper-o",
            "children"=>[
                array(
                    "id" => 121,
                    "label"=>"订单列表",
                    "url"=>"/order/list",
                    "icon"=>"fa fa-list",
                    "schemaApi"=>"get:/public/mis/pages/crud-order-list.json"
                ),
                // array(
                //     "id" => 122,
                //     "label"=>"结转记录",
                //     "url"=>"/order/transfer",
                //     "icon"=>"fa fa-retweet",
                //     "schemaApi"=>"get:/public/mis/pages/crud-order-transfer-list.json"
                // ),
                // array(
                //     "id" => 123,
                //     "label"=>"退款记录",
                //     "url"=>"/order/refund",
                //     "icon"=>"fa fa-reply",
                //     "schemaApi"=>"get:/public/mis/pages/crud-order-refund-list.json"
                // ),
                // array(
                //     "id" => 124,
                //     "label"=>"存额变更",
                //     "url"=>"/order/recharge",
                //     "icon"=>"fa fa-random",
                //     "schemaApi"=>"get:/public/mis/pages/crud-order-recharge-list.json"
                // ),
                array(
                    "id" => 126,
                    "label"=>"变更记录",
                    "url"=>"/order/change",
                    "icon"=>"fa fa-retweet",
                    "schemaApi"=>"get:/public/mis/pages/crud-order-change-list.json"
                ),                
                array(
                    "id" => 125,
                    "label"=>"绑定课程",
                    "url"=>"/order/band",
                    "icon"=>"fa fa-check-square-o",
                    "schemaApi"=>"get:/public/mis/pages/form-order-band.json"
                )
            ]
        ),
        array(
            "id" => 14,
            "label"=>"留学中心",
            "url"=>"/abroadplan",
            "icon"=>"fa fa-address-card",
            "children"=>[
                array(
                    "id" => 142,
                    "label"=>"服务列表",
                    "url"=>"/aporder/package/list",
                    "icon"=>"fa fa-reorder",
                    "schemaApi"=>"get:/public/mis/pages/crud-aporder-package-list.json"
                ),
                array(
                    "id" => 143,
                    "label"=>"订单列表",
                    "url"=>"/aporder/order/list",
                    "icon"=>"fa fa-calendar",
                    "schemaApi"=>"get:/public/mis/pages/crud-aporder-order-list.json"
                ),
                array(
                    "id" => 144,
                    "label"=>"变更记录",
                    "url"=>"/aporder/change/list",
                    "icon"=>"fa fa-retweet",
                    "schemaApi"=>"get:/public/mis/pages/crud-aporder-change-list.json"
                ),                    
                array(
                    "id" => 145,
                    "label"=>"绑定课程",
                    "url"=>"/aporder/band",
                    "icon"=>"fa fa-window-restore",
                    "schemaApi"=>"get:/public/mis/pages/form-abroadplan-order-band.json"
                ),
                array(
                    "id" => 141,
                    "label"=>"配置计划",
                    "url"=>"/abroadplan/lists",
                    "icon"=>"fa fa-list",
                    "schemaApi"=>"get:/public/mis/pages/crud-abroadplan-list.json"
                ),
                array(
                    "id" => 146,
                    "label"=>"配置检查项",
                    "url"=>"/abroadplan/confirm",
                    "icon"=>"fa fa-check-square-o",
                    "schemaApi"=>"get:/public/mis/pages/form-abroadplan-confirm.json"
                )                            
            ], 
        ),
        array(
            "id" => 3,
            "label"=>"班级管理",
            "url"=>"/group",
            "icon"=>"fa fa-envelope-o",
            "schemaApi"=>"get:/public/mis/pages/crud-group-list.json"
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
                    "schemaApi"=>"get:/public/mis/pages/form-schedule.json"
                ),
                array(
                    "id" => 42,
                    "label"=>"排课列表",
                    "url"=>"/schedule/schedulelist",
                    "icon"=>"fa fa-list",
                    "schemaApi"=>"get:/public/mis/pages/crud-schedule-list.json"
                ),
                array(
                    "id" => 43,
                    "label"=>"课表",
                    "url"=>"/schedule/calendar",
                    "icon"=>"fa fa-calendar",
                    "link"=>HOSTNAME . "platform"
                ),
                array(
                    "id" => 44,
                    "label"=>"操作日志",
                    "url"=>"/schedule/operationlog",
                    "icon"=>"fa fa-align-left",
                    "schemaApi"=>"get:/public/mis/pages/crud-log-schedule-list.json"
                ),
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
                    "schemaApi"=>"get:/public/mis/pages/crud-schedule-area-detail.json"
                ),
                array(
                    "id" => 102,
                    "label"=>"配置教室",
                    "url"=>"/schedule/arealist",
                    "icon"=>"fa fa-bars",
                    "schemaApi"=>"get:/public/mis/pages/crud-schedule-area-list.json"
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
                    "id" => 53,
                    "label"=>"账户变动明细表",
                    "url"=>"/records/account",
                    "icon"=>"fa fa-list",
                    "schemaApi"=>"get:/public/mis/pages/crud-records-account-history-list.json"
                ),
                array(
                    "id" => 55,
                    "label"=>"账户变动实时表",
                    "url"=>"/records/student",
                    "icon"=>"fa fa-list",
                    "schemaApi"=>"get:/public/mis/pages/crud-records-account-current-list.json"
                ),                
                array(
                    "id" => 51,
                    "label"=>"结算报表",
                    "url"=>"/records/lists",
                    "icon"=>"fa fa-list",
                    "schemaApi"=>"get:/public/mis/pages/crud-records-schedule-list.json"
                ),
                array(
                    "id" => 52,
                    "label"=>"订单信息明细表",
                    "url"=>"/records/order",
                    "icon"=>"fa fa-list",
                    "schemaApi"=>"get:/public/mis/pages/crud-records-order-list.json"
                ),
                array(
                    "id" => 56,
                    "label"=>"订单消耗实时表",
                    "url"=>"/records/order_current",
                    "icon"=>"fa fa-list",
                    "schemaApi"=>"get:/public/mis/pages/crud-records-order-current-list.json"
                ),
                array(
                    "id" => 57,
                    "label"=>"学管统计表",
                    "url"=>"/records/sop",
                    "icon"=>"fa fa-list",
                    "schemaApi"=>"get:/public/mis/pages/crud-records-sop-list.json"
                ),
                array(
                    "id" => 54,
                    "label"=>"教师课时",
                    "url"=>"/records/teacher",
                    "icon"=>"fa fa-list",
                    "schemaApi"=>"get:/public/mis/pages/crud-records-teacher-list.json"
                )
            ]
        ),
        array(
            "id" => 6,
            "label"=>"科目管理",
            "url"=>"/subject",
            "icon"=>"fa fa-server",
            "children"=>[
                array(
                    "id" => 61,
                    "label"=>"科目分类",
                    "url"=>"/subject/lists",
                    "icon"=>"fa fa-list",
                    "schemaApi"=>"get:/public/mis/pages/crud-subject-list.json"
                ),
                array(
                    "id" => 63,
                    "label"=>"绑定班型",
                    "url"=>"/subject/claszemap",
                    "icon"=>"fa fa-retweet",
                    "schemaApi"=>"get:/public/mis/pages/crud-claszemap-list.json"
                ),
            ], 
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
                    "schemaApi"=>"get:/public/mis/pages/crud-teacher-list.json"
                ),
                array(
                    "id" => 72,
                    "label"=>"锁定时间",
                    "url"=>"/teacher/lock",
                    "icon"=>"fa fa-lock",
                    "schemaApi"=>"get:/public/mis/pages/crud-teacher-lock-list.json"
                ),
            ]
        ),
        array(
            "id" => 8,
            "label"=>"校区管理",
            "url"=>"/area",
            "icon"=>"fa fa-street-view",
            "schemaApi"=>"get:/public/mis/pages/crud-area-list.json"
        ),
        array(
            "id" => 13,
            "label"=>"工单审批",
            "url"=>"/review",
            "icon"=>"fa fa-check-circle-o",
            "schemaApi"=>"get:/public/mis/pages/crud-review-list.json"
        ),
        array(
            "id" => 9,
            "label"=>"系统配置",
            "url"=>"/system",
            "icon"=>"fa fa-cog",
            "isSuper" => 1,
            "children"=>[
                array(
                    "id" => 91,
                    "label"=>"操作人员",
                    "url"=>"/system/admins",
                    "icon"=>"fa fa-list",
                    "schemaApi"=>"get:/public/mis/pages/crud-admin-list.json"
                ),
                array(
                    "id" => 92,
                    "label"=>"角色配置",
                    "url"=>"/system/roles",
                    "icon"=>"fa fa-bars",
                    "schemaApi"=>"get:/public/mis/pages/crud-roles-list.json"
                ),
                array(
                    "id" => 93,
                    "label"=>"生源地",
                    "url"=>"/system/birthplace",
                    "icon"=>"fa fa-server",
                    "schemaApi"=>"get:/public/mis/pages/crud-birthplace-list.json"
                ),
                array(
                    "id" => 94,
                    "label"=>"班型配置",
                    "url"=>"/subject/clasze",
                    "icon"=>"fa fa-calendar",
                    "schemaApi"=>"get:/public/mis/pages/crud-clasze-list.json"
                ),
            ]
        )
    ),
    "teacher" => array(
        array(
            "id" => 11,
            "label"=>"个人课表",
            "url"=>"/platform",
            "icon"=>"fa fa-calendar",
            "link"=>HOSTNAME . "details"
        ),
    ),
    "mode" => array(
        array(
            "id" => 4001,
            "label"=>"排课编辑",
            "tag" => "排课列表单项编辑能力, 包括上课时间,教师和教室等"
        ),
        array(
            "id" => 4002,
            "label"=>"排课删除",
        ),
        array(
            "id" => 4003,
            "label"=>"学员充值",
        ),
        array(
            "id" => 4004,
            "label"=>"学员退款",
        ),
        array(
            "id" => 4009,
            "label"=>"学员编辑",
        ),
        array(
            "id" => 4005,
            "label"=>"教师设置底薪",
        ),
        array(
            "id" => 4006,
            "label"=>"教师锁定删除",
            "tag" => "删除教师已设置的无法上课的锁定时间"
        ),
        array(
            "id" => 4007,
            "label"=>"审核操作",
        ),
        array(
            "id" => 4008,
            "label"=>"学员核心数据",
            "tag" => "除归属学管外, 赋予他人对学员金额/课时数据读写权限",
        ),
    ),
);