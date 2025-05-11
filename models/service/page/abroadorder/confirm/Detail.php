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

        $uids = array();
        foreach ($confirmData['content'] as $i => $v) {   
            foreach ($v["items"] as $ii => $vv) {
                if (!empty($vv['is_oc']) && !empty($vv['o_id'])) {
                    $uids[] = intval($vv['o_id']);
                }
                if (!empty($vv['is_sc']) && !empty($vv['s_id'])) {
                    $uids[] = intval($vv['s_id']);
                }
            }
        }
        $uids  = array_unique($uids);

        $userInfos = array();
        if (!empty($uids)) {
            $serviceData = new Service_Data_Profile();
            $userInfos = $serviceData->getUserInfoByUids($uids);
            $userInfos = array_column($userInfos, null, "uid");
        }

        foreach ($confirmData['content'] as $i => $v) {   
            $body = array();
            foreach ($v["items"] as $ii => $vv) {
                $operator = !empty($vv["o_id"]) && !empty($userInfos[$vv['o_id']]["nickname"]) ? $userInfos[$vv['o_id']]["nickname"] : "";
                $student = !empty($vv["s_id"]) && !empty($userInfos[$vv['s_id']]["nickname"]) ? $userInfos[$vv['s_id']]["nickname"] : "";

                $oLable = $sLable = "";
                if (!empty($vv["is_oc"]) && !empty($operator) && !empty($vv["o_time"])) {
                    $oLable = sprintf("%s/%s", $operator, date("Y-m-d H:i", $vv["o_time"]));
                }
                if (!empty($vv["is_sc"]) && !empty($student) && !empty($vv["s_time"])) {
                    $sLable = sprintf("%s/%s", $student, date("Y-m-d H:i", $vv["s_time"]));
                }
                $suLable = "";
                $suStatuts = true;
                if (!empty($vv["up_ext"])) {
                    $suLable = "下载附件";
                    $suStatuts = false;
                }

                $body[] = array(
                    array(
                        "type"=> "group",
                        "body"=> array(
                            array(
                                "type"=> "static",
                                "labelClassName"=> "text-muted",
                                "value"=> $vv['title'],
                                "columnRatio" => 6,
                                "desc" => empty($vv['sub_title']) ? "" : $vv['sub_title'],
                            ),
                            array(
                                "name"=> "oc_".$vv["key"],
                                "type"=> "checkbox",
                                "columnRatio" => 2,
                                "value" => !empty($vv["is_oc"]),
                                "disabled" => !empty($vv["is_oc"]) && !empty($vv["is_sc"]),
                                "option"=> "operator",
                                "desc" => $oLable,
                            ),
                            array(
                                "name"=> "sc_".$vv["key"],
                                "type"=> "checkbox",
                                "desc" => $sLable,
                                "value" => !empty($vv["is_sc"]),
                                "columnRatio" => 2,
                                "option"=> "student",
                                "maxSize" => 1048576 * 5,
                                "disabled"=>true,
                            ),
                            array(
                                "type"=> "input-file",
                                "name"=> "upload",
                                "columnRatio" => 1,
                                "accept"=> ".pdf,.txt,.doc,.docx,.jpg,.jpeg,.png",
                                "useChunk" => false,
                                "receiver"=> sprintf("/mapi/abroadorder/confirmupload?apackage_id=%s&key=%s", $apackageId, $vv["key"]),
                            ),
                            array(
                                "name"=> $vv["key"],
                                "label" => $suLable,
                                "level" => "link",
                                "type"=> "action",
                                "disabled" => $suStatuts,
                                "actionType"=> "download",
                                "useChunk" => false,
                                "api"=> sprintf("/mapi/abroadorder/confirmdown?apackage_id=%s&check_id=%s", $apackageId,  $vv['key']),
                                "columnRatio" => 1,
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