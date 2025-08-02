<?php

class Service_Page_Api_Mockmenu extends Zy_Core_Service{

    // 通用组件, 页面获取信息
    public function execute () {

        $pages = $this->getUserRolePageIds();
        // 无权限配置且不是超管, 没有权限查看
        if (empty($pages) && !$this->checkSuper()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        // 获取menu conf
        $menuConf = Zy_Helper_Config::getAppConfig("menu");
        $menuHead = $menuConf['head']; // 头部
        $menuCont = $menuConf['menu']; // 主体
        $menuTeacher = $menuConf['teacher']; // 教师独立TAB

        // 管理员直接返回
        if ($this->checkSuper()) {
            $menuHead['pages'][1]['children'] = array_merge($menuHead['pages'][1]['children'], $menuCont);
            return $menuHead;
        }

        // 教师预先加上个人课表
        if ($this->checkTeacher()) {
            $menuHead['pages'][1]['children'] = array_merge($menuHead['pages'][1]['children'], $menuTeacher);
        }

        // 根据用户pages更新menus
        foreach ($menuCont as $key => $item) {
            if (empty($item['children'])) {
                if (!in_array($item['id'], $pages)) {
                    unset($menuCont[$key]);
                    continue;
                }
            } else {
                foreach ($item['children'] as $ck => $citem) {
                    if (!in_array($citem['id'], $pages)) {
                        unset($item['children'][$ck]);
                        continue;
                    }
                }
                if (empty($item['children'])) {
                    unset($menuCont[$key]);
                    continue;
                }
                $item['children'] = array_values($item['children']);
            }
            $menuCont[$key] = $item;
        }

        $menuHead['pages'][1]['children'] = array_merge($menuHead['pages'][1]['children'],  array_values($menuCont));
        return $menuHead;
    }
}