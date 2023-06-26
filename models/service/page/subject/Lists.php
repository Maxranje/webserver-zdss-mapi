<?php

class Service_Page_Subject_Lists extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $pn = empty($this->request['page']) ? 1 : intval($this->request['page']);
        $rn = empty($this->request['perPage']) ? 20 : intval($this->request['perPage']);

        $pn = ($pn-1) * $rn;

        $name       = empty($this->request['name']) ? "" : strval($this->request['name']);
        $isSelect   = empty($this->request['isSelect']) ? false : true;

        $conds = array();

        if (!empty($name)) {
            $conds[] = "name like '%".$name."%'";
        }
        
        $serviceData = new Service_Data_Subject();

        $arrAppends[] = 'order by create_time desc';

        if(!$isSelect) {
            $arrAppends[] = "limit {$pn} , {$rn}";
        }
        
        $lists = $serviceData->getListByConds($conds, array(), NULL, $arrAppends);
        if ($isSelect) {
            return $this->formatSelect($lists);
        }

        $total = $serviceData->getTotalByConds($conds);
        return array(
            'rows' => $lists,
            'total' => $total,
        );
    }

    private function formatSelect($lists) {
        if (empty($lists)) {
            return array();
        }
        
        $options = array();
        foreach ($lists as $item) {
            if (!isset($options[$item['category1']])) {
                $options[$item['category1']] = array(
                    'label' => $item['category1'],
                    'value' => $item['category1'],
                );
            }
            if (!isset($options[$item['category1']]['children'][$item['category2']])) {
                $options[$item['category1']]['children'][$item['category2']] = array(
                    'label' => $item['category2'],
                    'value' => $item['category2'],
                    'children' => array(),
                );
            }
            $options[$item['category1']]['children'][$item['category2']]['children'][] = array(
                'label' => $item['name'],
                'value' => $item['id'],
            );
        }
        foreach ($options as $index => $item) {
            $item['children'] = array_values($item['children']);
            $options[$index] = $item;
        }
        return array('options' => array_values($options));
    }
}