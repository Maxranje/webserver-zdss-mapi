<?php

class Service_Page_Mock_Tag_Lists extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $sc         = empty($this->request['sc']) ? "" : trim($this->request['sc']);
        $isSelect   = empty($this->request['is_select']) ? false : true;
        $isCharts   = empty($this->request['is_charts']) ? false : true;
        $isRows     = empty($this->request['is_rows']) ? false : true;

        $serviceData = new Service_Data_Tag();

        $conds = array(
            "id > 0",
        );
        if (!empty($sc)) {
            $conds[] = "title like '%" . $sc . "%'";    
        }
        $lists = $serviceData->getListByConds($conds);
        if ($lists === false) {
            throw new Zy_Core_Exception(405, "获取标签异常,请重新刷新");
        }
        if ($isCharts) {
            return $this->buildCharts($lists);
        }

        if ($isRows) {
            return $this->buildRows($lists);
        }
        if ($isSelect) {
            return $this->buildSelect($lists);
        }
        return array();
    }

    public function buildCharts($items) {
        // 创建根节点
        $root = [
            'name'  => 'root',
            "value" => 0,
            'children' => []
        ];
        if (empty($items)) {
            return array($root);
        }

        // 按ID建立索引映射
        $idMap = [];
        foreach ($items as $item) {
            $item = array(
                "name" => $item["title"],
                "value" => $item["id"],
                "parent_id" => $item["parent_id"],
            );
            $idMap[$item['value']] = array_merge($item, ['children' => []]);
        }

        // 构建树结构
        foreach ($idMap as $id => &$node) {
            $parentId = $node['parent_id'];
            
            if ($parentId == 0) {
                // 直接添加到根节点
                $root['children'][] = &$node;
            } elseif (isset($idMap[$parentId])) {
                // 添加到父节点的children中
                $idMap[$parentId]['children'][] = &$node;
                unset($idMap[$parentId]["value"]);
            }
            // 如果父节点不存在，不做任何操作（自动丢弃）
        }
        unset($node); // 断开引用
        
        return array("charts" => $root);
    }

    public function buildRows($items) {
        // 创建根节点
        $root = [
            "id" => 0,
            'title' => 'root',
            "description" => "-",
            'children' => []
        ];
        if (empty($items)) {
            return array();
        }

        // 按ID建立索引映射
        $idMap = [];
        foreach ($items as $item) {
            $item = array(
                "title" => $item["title"],
                "id" => $item["id"],
                "description" => $item["description"],
                "parent_id" => $item["parent_id"],
                "create_time" => date("Y-m-d H:i:s", $item["create_time"]),
                "update_time" => date("Y-m-d H:i:s", $item["update_time"]),
            );
            $idMap[$item['id']] = array_merge($item, ['children' => []]);
        }

        // 构建树结构
        foreach ($idMap as $id => &$node) {
            $parentId = $node['parent_id'];
            
            if ($parentId == 0) {
                // 直接添加到根节点
                $root['children'][] = &$node;
            } elseif (isset($idMap[$parentId])) {
                // 添加到父节点的children中
                $idMap[$parentId]['children'][] = &$node;
                unset($idMap[$parentId]["value"]);
            }
            // 如果父节点不存在，不做任何操作（自动丢弃）
        }
        unset($node); // 断开引用

        $rows = empty($root["children"]) ?array() : $root["children"];
        return array(
            "rows" => $rows,
            "total" => count($rows),
        );
    }

    public function buildSelect($items) {
        // 创建根节点
        $root = array(
            "label" => "root",
            "children" => array(),
        );        
        if (empty($items)) {
            return array();
        }

        // 按ID建立索引映射
        $idMap = [];
        foreach ($items as $item) {
            $item = array(
                "label" => $item["title"],
                "value" => $item["id"],
                "parent_id" => $item["parent_id"],
            );
            $idMap[$item['value']] = array_merge($item, ['children' => []]);
        }

        // 构建树结构
        foreach ($idMap as $id => &$node) {
            $parentId = $node['parent_id'];
            
            if ($parentId == 0) {
                // 直接添加到根节点
                $root['children'][] = &$node;
            } elseif (isset($idMap[$parentId])) {
                // 添加到父节点的children中
                $idMap[$parentId]['children'][] = &$node;
                unset($idMap[$parentId]["value"]);
            }
            // 如果父节点不存在，不做任何操作（自动丢弃）
        }
        unset($node); // 断开引用

        $result[] = $root;
        return array('options' => array_values($root["children"]));
    }    
}