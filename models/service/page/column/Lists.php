<?php

class Service_Page_Column_Lists extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $teacherId = empty($this->request['teacher_id']) ? 0 : intval($this->request['teacher_id']);

        $conds = array();
        if ($teacherId > 0) {
            $conds['teacher_id'] = $teacherId;
        }
        
        $serviceData = new Service_Data_Column();
        $lists = $serviceData->getListByConds($conds);
        if (empty($lists)) {
            return array();
        }

        return $this->format($lists);
    }

    private function format ($lists) {

        $subjectIds = array();
        $teacherIds = array();
        foreach ($lists as $item) {
            $subjectIds[intval($item['subject_id'])] = intval($item['subject_id']);
            $teacherIds[intval($item['teacher_id'])] = intval($item['teacher_id']);
        }
        $subjectIds = array_values($subjectIds);
        $teacherIds = array_values($teacherIds);

        $serviceData = new Service_Data_Subject();
        $subjectInfos = $serviceData->getListByConds(array(sprintf("id in (%s)", implode(",", $subjectIds))));
        $subjectInfos = array_column($subjectInfos, null, 'id');

        $serviceData = new Service_Data_User_Profile();
        $teacherInfos = $serviceData->getListByConds(array(sprintf("uid in (%s)", implode(",", $teacherIds))));
        $teacherInfos = array_column($teacherInfos, null, 'uid');

        $result = array();
        foreach ($lists as $item) {
            if (empty($subjectInfos[$item['subject_id']]['name'])
                || empty($teacherInfos[$item['teacher_id']]['nickname'])) {
                continue;
            }
            $result[] = array(
                'subject_name' => $subjectInfos[$item['subject_id']]['name'],
                'teacher_name' => $teacherInfos[$item['teacher_id']]['nickname'],
                'teacher_id' => $item['teacher_id'],
                'subject_id' => $item['subject_id'],
                "price" => $item['price'],
                "number" => $item["number"],
                "price_info" => ($item['price'] / 100) . "元",
                "price_info2" => ($item['price'] / 100),
                "muilt_price_info" => ($item['muilt_price'] / 100) . "元",
                "muilt_price_info2" => ($item['muilt_price'] / 100),
            );
        }

        return array(
            "type"=> "cards",
            "data"=> [
                'items' => $result,
            ],
            "source" => '${items}',
            "card"=> [
                "body"=> [
                    [
                        "label"=> "课程名",
                        "name"=> "subject_name"
                    ],
                    [
                        "label"=> "课时单价",
                        "name"=> "price_info"
                    ],
                    [
                        "label"=> "阈值人数",
                        "name"=> "number"
                    ],
                    [
                        "label"=> "超阈值单价",
                        "name"=> "muilt_price_info"
                    ]
                ],
                "actions"=> [
                    [
                        "type"=> "button",
                        "level"=> "link",
                        "icon"=> "fa fa-pencil",
                        "actionType"=> "dialog",
                        "dialog"=> [
                            "title"=> "查看详情",
                            "size"=> "lg",
                            "body"=>[
                                "type"=> "form",
                                "name"=> "update-column-form",
                                "api"=> [
                                    "method"=> "post",
                                    "url"=> "/mapi/column/update",
                                    "dataType"=> "form"
                                ],
                                "body"=> [
                                    [
                                        "type"=> "input-text",
                                        "name"=> "teacher_id",
                                        "label"=> "教师ID",
                                        "disabled"=> true
                                    ],
                                    [
                                        "type"=> "divider"
                                    ],
                                    [
                                        "type"=> "input-text",
                                        "name"=> "subject_id",
                                        "label"=> "科目ID",
                                        "disabled"=> true
                                    ],
                                    [
                                        "type"=> "divider"
                                    ],
                                    [
                                        "type"=> "input-text",
                                        "name"=> "price",
                                        "label"=> "课时单价",
                                        "value"=>'${price_info2}',
                                        "addOn"=> [
                                            "type"=> "text",
                                            "label"=> "元"
                                        ],
                                        "desc"=> "一小时单价, 元为单位, 保留小数点后两位, 谨慎填写价格, 0为免费课"
                                    ],
                                    [
                                        "type"=> "divider"
                                    ],
                                    [
                                        "type"=> "input-text",
                                        "name"=> "number",
                                        "label"=> "人数阈值",
                                        "value"=>'${number}',
                                        "addOn"=> [
                                            "type"=> "text",
                                            "label"=> "人"
                                        ],
                                        "desc"=> "超过阈值人数可以按新价格给教师算价, 人数必须大于1人, 否则保存失败"
                                    ],
                                    [
                                        "type"=> "divider"
                                    ],
                                    [
                                        "type"=> "input-text",
                                        "name"=> "muilt_price",
                                        "label"=> "课时单价",
                                        "value"=>'${muilt_price_info2}',
                                        "addOn"=> [
                                            "type"=> "text",
                                            "label"=> "元"
                                        ],
                                        "desc"=> "超阈值一小时单价, 元为单位, 保留小数点后两位, 谨慎填写价格, 0为免费课"
                                    ],
                                ]
                            ]
                        ]
                    ],
                    [
                        "type"=> "button",
                        "icon"=> "fa fa-times text-danger",
                        "actionType"=> "ajax",
                        "tooltip"=> "删除",
                        "confirmText"=> "您确认要删除课程, 删除课程会删除所有未上课的排课?",
                        "api"=> [
                            "method"=> "get",
                            "url"=> '/mapi/column/delete?teacher_id=$teacher_id&subject_id=$subject_id'
                        ]
                    ]
                ]
            ]
        );
    }
}