<?php

class Service_Page_Mock_Source_Lists extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限操作");
        }

        $pn         = empty($this->request['page']) ? 0 : intval($this->request['page']);
        $rn         = empty($this->request['perPage']) ? 0 : intval($this->request['perPage']);
        $isSelect   = empty($this->request['is_select']) ? false : true;

        $pn = ($pn-1) * $rn;

        $serviceData = new Service_Data_QuestionSource();
        $append = array();
        if (!$isSelect) {
            $append[] = "limit {$pn} , {$rn}";
        }
        $lists = $serviceData->getListByConds(array(), array(), null, $append);
        if (empty($lists)) {
            return array();
        }

        if ($isSelect) {
            return $this->formatSelect($lists);
        }

        $total = $serviceData->getTotalByConds(array());
        return array(
            'rows' => $this->format($lists),
            'total' => $total,
        );
    }

    private function format($lists) {
        $lists = array_column($lists, null, "id");
        foreach ($lists as $v) {
            if (empty($v["parent_id"])) {
                continue;
            }
            if (empty($lists[$v["parent_id"]])) {
                continue;
            }
            $parent = $lists[$v["parent_id"]];
            $result[] = array(
                "id"        => $parent["id"],
                "name"      => $parent["name"],
                "sub_id"    => $v["id"],
                "sub_name"  => $v["name"],
                "create_time" => date("Y年m月d日 H:i:s", $v['create_time']),
                "update_time" => date("Y年m月d日 H:i:s", $v['update_time']),
            );
        }

        usort($result, function ($a,$b) {
            return $a['id']>$b['id'];
        });
        return $result;
    }

    private function formatSelect($lists) {
        $lists = array_column($lists, null, "id");
        $options = array();
        foreach ($lists as $id => $v) {
            if (empty($v["parent_id"])) {
                $options[$id] = array(
                    'label' => $v['name'],
                    "children" => array(),
                );
            }            
        }
        foreach ($lists as $id => $v) {
            $pid = $v["parent_id"];
            if ($pid <= 0 || empty($options[$pid])) {
                continue;
            }
            $options[$pid]["children"][] = array(
                'label' => $v['name'],
                'value' => $v['id'],                
            );
        }
        foreach ($options as $k => $v) {
            if (empty($v["children"])) {
                unset($options[$k]);
            }
        }
        return array_values($options);
    }
}