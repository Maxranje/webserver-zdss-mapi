<?php

class Service_Page_Subject_Lists extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $pn         = empty($this->request['page']) ? 1 : intval($this->request['page']);
        $rn         = empty($this->request['perPage']) ? 20 : intval($this->request['perPage']);
        $name       = empty($this->request['name']) ? "" : strval($this->request['name']);
        $isSelect   = empty($this->request['is_select']) ? false : true;
        $isPrice    = empty($this->request['is_price']) ? false : true;

        $pn = ($pn-1) * $rn;

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
            return $this->formatSelect($lists, $isPrice);
        }

        $total = $serviceData->getTotalByConds($conds);
        return array(
            'rows' => $lists,
            'total' => $total,
        );
    }

    private function formatSelect($lists, $isPrice = false) {
        if (empty($lists)) {
            return array();
        }
        
        $options = array();
        foreach ($lists as $item) {
            if (!isset($options[$item['category']])) {
                $options[$item['category']] = array(
                    'label' => $item['category'],
                    'value' => $item['category'],
                    'children' => array(),
                );
            }
            $options[$item['category']]['children'][] = array(
                'label' => $isPrice ? sprintf("%s (%s元)", $item['name'] , $item['price_info']) : $item['name'],
                'value' => $item['id'],
            );
        }
        return array('options' => array_values($options));
    }
}