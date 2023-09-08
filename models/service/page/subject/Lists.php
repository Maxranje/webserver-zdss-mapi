<?php

class Service_Page_Subject_Lists extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $pn             = empty($this->request['page']) ? 1 : intval($this->request['page']);
        $rn             = empty($this->request['perPage']) ? 20 : intval($this->request['perPage']);
        $subjectName    = empty($this->request['subject_name']) ? "" : trim($this->request['subject_name']);
        $orderDir       = empty($this->request['orderDir']) ? "desc" : trim($this->request['orderDir']);
        $orderBy        = empty($this->request['orderBy']) ? "" : trim($this->request['orderBy']);
        $isSelect       = empty($this->request['is_select']) ? false : true;
        $isParent       = empty($this->request['is_parent']) ? false : true;

        $pn = ($pn-1) * $rn;

        $serviceData = new Service_Data_Subject();

        $conds = array(
            "parent_id" => 0,
        );
        if (!empty($subjectName)) {
            $conds[] = "name like '%".$subjectName."%'";
        }
        
        $ob = 'order by name';
        if ($orderBy == "subject_name") {
            $ob = "order by name " . ($orderDir == "desc" ? "desc" : "asc");
        }
        $arrAppends[] = $ob;
        if(!$isSelect) {
            $arrAppends[] = "limit {$pn} , {$rn}";
        }
        
        $lists = $serviceData->getListByConds($conds, array(), NULL, $arrAppends);
        if (empty($lists)) {
            return array();
        }

        if ($isSelect && $isParent) {
            return $this->formatParent($lists);
        }
        if (!$isParent) {
            $lists = $this->formatDefault($lists);
        }
        if ($isSelect) {
            $lists = $this->formatSelect($lists);
        }

        $total = $serviceData->getSubjectTotalByConds($conds);
        return array(
            'rows' => $lists,
            'total' => $total,
        );
    }

    private function formatDefault ($lists) {

        $parentIds = Zy_Helper_Utils::arrayInt($lists, "id");
        $serviceData = new Service_Data_Subject();
        $subInfos = $serviceData->getListByConds(array(sprintf("parent_id in (%s)", implode(",", $parentIds))));
        $subInfos = array_column($subInfos, null, "id");

        $result = array();
        foreach ($lists as $item) {
            $tmp = array();
            $tmp['subject_id']      = $item['id'];
            $tmp['subject_name']    = $item['name'];
            $tmp['subject_desc']    = $item['descs'];
            $tmp['price']           = $item['price'];   
            $tmp['price_info']      = sprintf("%.2f", $tmp['price'] / 100);
            $tmp['create_time']     = date("Y-m-d H:i:s", $item['create_time']);
            $tmp['update_time']     = date("Y-m-d H:i:s", $item['update_time']);
            
            foreach ($subInfos as $k => $v) {
                if ($v['parent_id'] != $item['id']) {
                    continue;
                }
                $tmp['children'][] = array(
                    'subject_id'   => $v['id'],
                    'subject_name' => $v['name'],
                    'subject_desc' => $v['descs'],
                    'price'        => "-",
                    'price_info'   => "-",
                    'parent_id'    => $v['parent_id'],
                    'create_time'  => date("Y-m-d H:i:s", $v['create_time']),
                    'update_time'  => date("Y-m-d H:i:s", $v['update_time']),
                );
                unset($subInfos[$k]);
            }

            $subInfos = array_values($subInfos);
            $result[] = $tmp;
        }
        return $result;
    }

    private function formatSelect($lists) {
        if (empty($lists)) {
            return array();
        }
        
        $options = array();
        foreach ($lists as $item) {
            if (empty($item['children'])) {
                continue;
            }
            $op = array(
                'label' => $item['subject_name'],
                'value' => $item['subject_id'],
                "children" => array(),
            ) ;
            foreach ($item['children'] as $v) {
                $op['children'][] = array(
                    'label' => $v['subject_name'],
                    'value' => $v['subject_id'],
                );
            }
            $options[] = $op;
        }
        return $options;
    }

    private function formatParent($lists) {
        if (empty($lists)) {
            return array();
        }
        
        $options = array();
        foreach ($lists as $item) {
            $op = array(
                'label' => $item['name'],
                'value' => $item['id'],
            );
            $options[] = $op;
        }
        return $options;
    }
}