<?php

class Service_Page_Area_Lists extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $serviceData = new Service_Data_Area();

        $lists = $serviceData->getList();   
        $lists = $this->format ($lists);

        return array(
            'lists' => $lists,
            'total' => count($lists),
        );
    }

    public function format($lists) {
        $result = array();
        foreach ($lists as $item) {
            if (empty($item['rooms']) || !is_array($item['rooms'])) {
                continue;
            }
            foreach ($item['rooms'] as $value) {
                $result[] = array(
                    'aid' => $item['id'],
                    'area_name' => $item['name'],
                    'room_name' => $value['name'],
                    'rid' => $value['id'],
                    'area_time' => date("Y-m-d H:i:s", $item['create_time']),
                    'room_time' => date("Y-m-d H:i:s", $value['create_time']),
                );
            }
        }
        return $result;
    }
}