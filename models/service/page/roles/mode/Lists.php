<?php

class Service_Page_Roles_Mode_Lists extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkSuper()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $menuConf = Zy_Helper_Config::getAppConfig("menu");
        $menuConf = $menuConf['mode']; // 主体

        $result = array();
        foreach ($menuConf as $item) {
            if (!empty($item['isSuper'])) {
                continue;
            }
            $tmp = array(
                "label" => $item['label'],
                "value" => $item['id'],
                "tag" => empty($item["tag"]) ? "" : $item["tag"],
            );
            $result[] = $tmp;
        }
        return $result;
    }
}