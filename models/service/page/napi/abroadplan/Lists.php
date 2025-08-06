<?php

class Service_Page_Napi_Abroadplan_Lists extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkStudent()) {
            throw new Zy_Core_Exception(405, "无权限");
        }
        $serviceData = new Service_Data_Aporderpackage();
        $lists = $serviceData->getApackagesByUid(intval($this->adption["userid"]));
        if (empty($lists)) {
            return array();
        }
        return array(
            "list" => $this->formatBase($lists),
        );
    }
    
    public function formatBase ($lists) {

        $abroadplanIds = Zy_Helper_Utils::arrayInt($lists, "abroadplan_id");
        $apackageIds = Zy_Helper_Utils::arrayInt($lists, "id");
        $uids = Zy_Helper_Utils::arrayInt($lists, "operator");

        $serviceData = new Service_Data_Apackageconfirm();
        $confirmInfos = $serviceData->getConfirmByIds($apackageIds);
        $confirmInfos = array_column($confirmInfos, null, "apackage_id");     

        foreach ($confirmInfos as $v) {   
            if (!empty($v['content'])) {
                foreach ($v["content"] as $content) {
                    if (!empty($content["items"])) {
                        foreach ($content['items'] as $vv) {
                            if (!empty($vv['is_oc']) && !empty($vv['o_id'])) {
                                $uids[] = intval($vv['o_id']);
                            }
                        }
                    }
                }
            }
        }
        $uids  = array_unique($uids);        

        // user profile
        $serviceUser = new Service_Data_Profile();
        $userInfo = $serviceUser->getUserInfoByUids($uids);
        $userInfo = array_column($userInfo, null , "uid");             

        $serviceData = new Service_Data_Abroadplan();
        $abroadplanInfos = $serviceData->getAbroadplanByIds($abroadplanIds);
        $abroadplanInfos = array_column($abroadplanInfos, null, "id");

        foreach ($lists as $key => $value) {
            if (empty($abroadplanInfos[$value['abroadplan_id']]["name"])) {
                continue;
            }
            $tmp = array(
                "id"            => $value["id"],
                "name"          => $abroadplanInfos[$value['abroadplan_id']]["name"],
                "status"        => $value["state"],
                "startDate"     => date("Y-m-d", $value['create_time']),
                "operator"      => empty($userInfo[$value['operator']]["nickname"]) ? "-" : $userInfo[$value['operator']]["nickname"],

                // 数据获取
                "progress"      => 0,
                "completedTasks"=> 0,
                "totalTasks"    => 0,
                "pendingTasks"  => 0,
                "checklist"     => array(),
            );

            // 处理检查项
            if (!empty($confirmInfos[$value["id"]]['content'])) {
                foreach ($confirmInfos[$value["id"]]['content'] as $index => $v) {
                    if (empty($v["items"])) {
                        continue;
                    }                    
                    $tmpConfirm= array(
                        "id" => ($index + 1) * 10,
                        "title" => $v['title'],
                        "items" => array(),
                    );
                    foreach ($v["items"] as $vv) {
                        $downloadPath = "";
                        if (!empty($vv['up_ext'])) {
                            $downloadPath = Zy_Helper_Config::getConfig('config')['upload_path'];
                            $downloadPath = sprintf("%s/%s", $downloadPath, $vv["key"] . "." . $vv["up_ext"]);
                        }
                        $tmpConfirm["items"][] = array(
                            "key" => "sc_" . $vv["key"],
                            "title" => $vv["title"],
                            "description" => empty($vv["sub_title"]) ? "" : $vv["sub_title"],
                            "teacherCompleted" =>  !empty($vv["is_oc"]) ? 1 : 0,
                            "teacherCompletedTime" => !empty($vv["o_time"]) ? date("Y-m-d H:i", $vv["o_time"]) : "",
                            "teacherCompletedBy" => !empty($vv["o_id"]) && !empty($userInfo[$vv['o_id']]["nickname"]) ? $userInfo[$vv['o_id']]["nickname"] : "",
                            "studentCompleted" => !empty($vv["is_sc"]) ? 1:0,
                            "studentCompletedTime" => !empty($vv["s_time"]) ? date("Y-m-d H:i", $vv["s_time"]) : "",
                            "downloadUrl" => $downloadPath,
                        );
                        $tmp["totalTasks"]++;
                        if (!empty($vv["is_sc"]) && !empty($vv["is_oc"])) {
                            $tmp["completedTasks"] ++;
                        } else {
                            $tmp["pendingTasks"] ++;
                        }
                    }
                    $tmp['checklist'][] = $tmpConfirm;
                }
                if ($tmp["totalTasks"] > 0) {
                    $tmp["progress"] = floatval(sprintf("%.2f", $tmp["completedTasks"] / $tmp["totalTasks"])) * 100;
                }
            }
            $lists[$key] = $tmp;
        }
        return array_values($lists);
    }
}   