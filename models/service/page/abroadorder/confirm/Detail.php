<?php
// 服务双向检查详情
class Service_Page_Abroadorder_Confirm_Detail extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $apackageId = empty($this->request['apackage_id']) ? 0 : intval($this->request['apackage_id']);
        $isEdit     = empty($this->request['is_edit']) ? false : true;
        if ($apackageId <= 0) {
            return array();
        }

        if (!$isEdit) {
            return $this->formatListPage($apackageId);
        } else {
            return $this->formatEditPage($apackageId);
        }
    }

    public function formatEditPage($apackageId) {
        $result= array(
            "type"=>"combo",
            "name"=>sprintf("confirm_%d_%d", time(), mt_rand(10000, 99999)),
            "label"=>"新增分类",
            "multiple"=>true,
            "multiLine"=>true,
            "minLength" =>1,
            "items"=>[
                array(
                    "name"=>"title",
                    "label"=>"检查项分类Label",
                    "type"=>"input-text",
                    "placeholder"=>"请输入检查项分类Label",
                    "mode"=>"horizontal",
                    "required"=>true,
                    "desc" =>"为学生端展示考虑, 字数不能超过100切只支持 中文/英文/数字"
                ),
                array(
                    "type"=>"combo",
                    "name"=>"items",
                    "label"=>"检查项分类 - 正文check项",
                    "multiple"=>true,
                    "multiLine"=>true,
                    "mode"=>"horizontal",
                    "required"=>true,
                    "minLength" =>1,
                    "items"=>[
                        array(
                            "name"=>"title",
                            "label"=>"单项Label",
                            "type"=>"input-text",
                            "placeholder"=>"",
                            "mode"=>"horizontal",
                            "required"=>true,
                            "size"=>"full"
                        ),
                        array(
                            "name"=>"sub_title",
                            "label"=>"单项描述",
                            "type"=>"input-text",
                            "placeholder"=>"",
                            "mode"=>"horizontal",
                            "size"=>"full",
                            "desc"=>"每个check项底部会的有提示语, 非必需"
                        ),
                        array(
                            "name"=>"key",
                            "type"=>"input-text",
                            "hidden"=>true
                        )
                    ]
                )
            ]      
        );

        $serviceData = new Service_Data_Apackageconfirm();
        $confirmData = $serviceData->getConfirmById($apackageId);
        if (empty($confirmData["content"])) {
            return $result;
        }

        $result["value"] = $confirmData['content'];
        return $result;
    }


    public function formatListPage($apackageId) {
        $result= array();

        $serviceData = new Service_Data_Apackageconfirm();
        $confirmData = $serviceData->getConfirmById($apackageId);
        if (empty($confirmData["content"])) {
            return $result;
        }

        foreach ($confirmData['content'] as $i => $v) {   
            $body = array();
            foreach ($v["items"] as $ii => $vv) {
                $body[] = array(
                    array(
                        "type"=> "group",
                        "body"=> array(
                            array(
                                "type"=> "static",
                                "labelClassName"=> "text-muted",
                                "value"=> $vv['title'],
                                "columnRatio" => 8,
                                "desc" => empty($vv['sub_title']) ? "" : $vv['sub_title'],
                            ),
                            array(
                                "name"=> "oc_".$vv["key"],
                                "type"=> "checkbox",
                                "columnRatio" => 2,
                                "value" => !empty($vv["is_oc"]),
                                "label"=> "operator",
                            ),
                            array(
                                "name"=> "sc_".$vv["key"],
                                "type"=> "checkbox",
                                "label"=> "student",
                                "value" => !empty($vv["is_sc"]),
                                "columnRatio" => 2,
                                "disabled"=>true,
                            ),
                        )
                    ),
                    array(
                        "type"=> "divider"
                    ),
                );
            }
            $result[] = array(
                "type"=> "fieldSet",
                "title"=> $v["title"],
                "mode"=> "vertical",
                "collapsable"=> true,
                "body"=> $body
            );
        }

        return $result;
    }
}