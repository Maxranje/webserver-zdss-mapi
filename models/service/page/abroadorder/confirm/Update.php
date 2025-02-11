<?php

class Service_Page_Abroadorder_Confirm_Update extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }
        $apackageId   = empty($this->request['apackage_id']) ? 0 : intval($this->request['apackage_id']);
        $isCover      = empty($this->request['is_cover']) ? false : true; // 更新检查内容
        $isReset      = empty($this->request['is_reset']) ? false : true; // 重置检查内容
        $isCheck      = empty($this->request['is_check']) ? false : true; // 更新选项

        // throw new Zy_Core_Exception(405, "无权限查看");

        $serviceData = new Service_Data_Aporderpackage();
        $apackageInfo = $serviceData->getAbroadpackageById($apackageId);
        if (empty($apackageInfo)) {
            throw new Zy_Core_Exception(405, "操作失败, 服务不存在, 无法操作");
        }
        if (!in_array($apackageInfo["state"], Service_Data_Aporderpackage::APORDER_STATUS_ABLE_MAP)) {
            throw new Zy_Core_Exception(405, "操作失败, 服务不再有效状态内不能进行操作, 请确认服务状态");
        }

        if ($isCheck) {
            return $this->check($apackageId, $apackageInfo);
        }
        if ($isReset) {
            throw new Zy_Core_Exception(405, "操作失败, 该能力已经下线");
            //return $this->reset($apackageId, $apackageInfo);
        }
        if ($isCover) {
            return $this->cover($apackageId, $apackageInfo);
        }

        throw new Zy_Core_Exception(405, "操作失败, 参数异常");
    }

    public function cover($apackageId, $apackageInfo) {
        $confirm = array();
        foreach ($this->request as $k => $v) {
            strpos($k, "confirm") !== false && $confirm = $v;
        }
        if (empty($confirm) || $apackageId <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 参数为空");
        }

        $serviceData = new Service_Data_Apackageconfirm();
        $confirmData = $serviceData->getConfirmById($apackageId);
        $originContent = empty($confirmData["content"]) ? array() : $confirmData["content"];

        $studentCheckItems = array();
        if (!empty($originContent)) {
            foreach ($originContent as $v) {
                foreach ($v["items"] as $vv) {
                    if (isset($vv["is_sc"])) {
                        $studentCheckItems[$vv["key"]] = $vv;
                    }
                }
            }
        }

        foreach ($confirm as $index => &$conf) {
            $conf["title"] = preg_replace("/[^\x{4e00}-\x{9fa5}\w]/u", '', $conf["title"]);
            if (empty($conf["title"])) {
                throw new Zy_Core_Exception(405, sprintf("操作失败, 第%s个分类label为空", $index + 1));
            }
            if (mb_strlen($conf["title"]) > 100) {
                throw new Zy_Core_Exception(405, sprintf("操作失败, 第%s个分类label大于100个字", $index + 1));
            }
            if (empty($conf["items"])) {
                throw new Zy_Core_Exception(405, sprintf("操作失败, 第%s个分类正文项为空", $index + 1));
            }
            foreach ($conf["items"] as $i => &$confItem) {
                $confItem["title"] = preg_replace("/[^\x{4e00}-\x{9fa5}\w]/u", '', $confItem["title"]);
                if (!empty($confItem["sub_title"])) {
                    $confItem["sub_title"] = preg_replace("/[^\x{4e00}-\x{9fa5}\w]/u", '', $confItem["sub_title"]);
                }
                if (empty($confItem["key"])) {
                    $confItem["key"] = sprintf("key_%d_%d_%d_%d", $apackageId, time(), mt_rand(10000, 99999), $index * 100 + $i);
                } else {
                    if (isset($studentCheckItems[$confItem['key']])) {
                        if (($confItem["title"] != $studentCheckItems[$confItem['key']]["title"])) {
                            throw new Zy_Core_Exception(405, sprintf("操作失败, 第%s个分类中%s个配置学员已经选中, 不能修改lable", $index + 1, $i + 1));
                        }
                        $subTitle1 = empty($studentCheckItems[$confItem['key']]["sub_title"]) ? "" : $studentCheckItems[$confItem['key']]["sub_title"];
                        $subTitle2 = empty($confItem["sub_title"]) ? "" : $confItem["sub_title"];
                        if ( $subTitle1 != $subTitle2) {
                            throw new Zy_Core_Exception(405, sprintf("操作失败, 第%s个分类中%s个配置学员已经选中, 不能修改单项描述", $index + 1, $i + 1));
                        }                        
                        unset($studentCheckItems[$confItem['key']]); // 判断是否为空即可
                    }
                }
                if (empty($confItem["title"])) {
                    throw new Zy_Core_Exception(405, sprintf("操作失败, 第%s个分类中%s个配置的单项label为空", $index + 1, $i + 1));
                }
                if (mb_strlen($confItem["title"]) > 100) {
                    throw new Zy_Core_Exception(405, sprintf("操作失败, 第%s个分类中%s个配置的单项label超100字", $index + 1, $i + 1));
                }
                if (!empty($confItem["sub_title"]) && mb_strlen($confItem["sub_title"]) > 100) {
                    throw new Zy_Core_Exception(405, sprintf("操作失败, 第%s个分类中%s个配置的单项备注超100字", $index + 1, $i + 1));
                }
            }
        }

        if (!empty($studentCheckItems)) {
            $listTitle = array_column($studentCheckItems, "title");
            throw new Zy_Core_Exception(405, sprintf("操作失败, 学员check选项被删除(该项不可修改和删除), 请重新检查, 对应标题为: %s", implode(",", $listTitle)));
        }

        if (empty($confirmData)) {
            $profile = [
                "content"       => json_encode($confirm), 
                "abroadplan_id" => intval($apackageInfo["abroadplan_id"]),
                "apackage_id"   => $apackageId,
                "operator"      => OPERATOR,
                "create_time"   => time() , 
                "update_time"   => time() , 
            ];
            $ret = $serviceData->create($profile);
        } else {
            $profile = [
                "content"       => json_encode($confirm), 
                "operator"      => OPERATOR,
                "update_time"   => time() , 
            ];
            $ret = $serviceData->update($confirmData['id'], $profile);
        }
        if ($ret == false) {
            throw new Zy_Core_Exception(405, "配置失败, 请重试");
        }
        return array();
    }

    // 设置
    public function reset($apackageId, $apackageInfo) {
        if ($apackageId <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 需要先选服务");
        }

        $serviceData = new Service_Data_Abroadplanconfirm();
        $confirm = $serviceData->getConfirmById(intval($apackageInfo["abroadplan_id"]));
        if (empty($confirm["content"])) {
            throw new Zy_Core_Exception(405, "操作失败, 所属计划的检查项配置不存在, 无法操作");
        }

        $serviceData = new Service_Data_Apackageconfirm();
        $confirmInfo = $serviceData->getConfirmById($apackageId);
        if (empty($confirmInfo)) {
            $profile = [
                "content"       => json_encode($confirm['content']), 
                "abroadplan_id" => intval($apackageInfo["abroadplan_id"]),
                "apackage_id"   => $apackageId,
                "operator"      => OPERATOR,
                "create_time"   => time() , 
                "update_time"   => time() , 
            ];
            $ret = $serviceData->create($profile);
        } else {
            $profile = [
                "content"       => json_encode($confirm['content']), 
                "operator"      => OPERATOR,
                "update_time"   => time() , 
            ];
            $ret = $serviceData->update($confirmInfo['id'], $profile);
        }
        if ($ret == false) {
            throw new Zy_Core_Exception(405, "配置失败, 请重试");
        }
        return array();
    }

    // 更新check项
    public function check($apackageId, $apackageInfo) {
        if ($apackageId <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 需要先选服务");
        }

        $serviceConfirm = new Service_Data_Apackageconfirm();
        $confirmData = $serviceConfirm->getConfirmById($apackageId);
        if (empty($confirmData["content"])) {
            throw new Zy_Core_Exception(405, "操作失败, 检查项配置不存在, 无法操作");
        }

        $content = $confirmData["content"];
        foreach($content as $ci => &$v) {
            foreach ($v["items"] as $cii => &$vv) {
                if (empty($vv["key"])) {
                    continue;
                }

                $key = "oc_".$vv["key"];
                if (isset($this->request[$key])) {
                    $checkValue = $this->request[$key] == "true" ? 1 : 0;
                    $hasValue = empty($vv["is_oc"]) ? 0 : 1;
                    if ($checkValue == $hasValue) {
                        continue;
                    } else {
                        if (!empty($vv["is_sc"]) && !empty($vv["is_oc"])) {
                            throw new Zy_Core_Exception(405, "操作失败, 双方选定, 不可修改");
                        } else {
                            $vv["is_oc"] = $this->request[$key] == "true" ? 1 : 0;
                            $vv['o_id'] = OPERATOR;
                            $vv["o_time"] = time();
                        }
                    }
                }
            }
        }

        $ret = $serviceConfirm->update(intval($confirmData["id"]), array("content" => json_encode($content)));
        if ($ret == false) {
            throw new Zy_Core_Exception(405, "提交失败, 请重试");
        }
        return array();
    }    
}