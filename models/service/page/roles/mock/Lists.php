<?php

class Service_Page_Roles_Mock_Lists extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkSuper()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $menuConf = Zy_Helper_Config::getAppConfig("navigation");
        $menuConf = $menuConf['menu']; // 主体

        $result = array();
        foreach ($menuConf as $item) {
            if (!empty($item['isSuper'])) {
                continue;
            }
            $tmp = array(
                "label" => $item['label'],
                "value" => $item['id'],
            );
            if (!empty($item['children'])) {
                $tmp['children'] = array();
                foreach ($item['children'] as $v) {
                    if (!empty($v['isSuper'])) {
                        continue;
                    }
                    $tmp['children'][] = array(
                        "label" => $v['label'],
                        "value" => $v['id'],
                    );
                }
                if (empty($tmp['children'])) {
                    unset($tmp['children']);
                }
            }
            $result[] = $tmp;
        }
        return $result;
    }
}