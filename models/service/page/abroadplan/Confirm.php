<?php

// 计划默认检查项
class Service_Page_Abroadplan_Confirm extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $confirm = empty($this->request['confirm']) ? array() : $this->request['confirm'];
        $abroadplanId = empty($this->request['id']) ? 0 : intval($this->request['id']);
        if (empty($confirm) || $abroadplanId <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 参数为空");
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
                    $confItem["key"] = sprintf("key_%d_%d_%d_%d", $abroadplanId, time(), mt_rand(10000, 99999), $index * 100 + $i);
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

        $serviceData = new Service_Data_Abroadplanconfirm();
        $confirmData = $serviceData->getConfirmById($abroadplanId);
        if (empty($confirmData)) {
            $profile = [
                "content"       => json_encode($confirm), 
                "abroadplan_id" => $abroadplanId,
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
}