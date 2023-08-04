<?php

class Service_Page_Area_Lists extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }
        $is_onlyarea    = empty($this->request['is_onlyarea']) ? false : true;
        $is_withroom    = empty($this->request['is_withroom']) ? false : true;
        $is_tips        = empty($this->request['is_tips']) ? false : true;

        $serviceData = new Service_Data_Area();

        $lists = $serviceData->getAreaListByConds(array("id > 0"));   
        if ($is_onlyarea) {
            return $this->formatOnlyArea($lists, $is_tips);
        }
        if ($is_withroom) {
            return $this->formatWithRoom($lists);
        }

        $lists = $this->formatDefault ($lists);
        return array(
            'lists' => $lists,
            'total' => count($lists),
        );
    }

    private function formatDefault($lists) {
        if (empty($lists)) {
            return array();
        }

        $serviceData = new Service_Data_Area();
        $roomLists = $serviceData->getRoomListByConds(array("id > 0"));
        if (empty($roomLists)) {
            return array();
        }

        $lists = array_column($lists, null, "id");

        foreach ($roomLists as $room) {
            if (isset($lists[$room['area_id']])) {
                $lists[$room['area_id']]['rooms'][] = $room;
            }
        }

        $lists = array_values($lists);

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
                    "is_online" => $item['is_online'],
                    'area_time' => date("Y年m月d日", $item['create_time']),
                    'room_time' => date("Y年m月d日", $value['create_time']),
                );
            }
        }
        return $result;
    }

    private function formatOnlyArea($lists, $is_tips = false) {
        if (empty($lists)) {
            return array();
        }

        $options = array();
        if ($is_tips) {
            $options[] = array(
                'label' => "无教室&过滤Online校区",
                'value' => -1,
            );
        }
        foreach ($lists as $item) {
            $options[] = array(
                'label' => "校区: " . $item['name'],
                'value' => intval($item['id']),
            );
        }
        return array('options' => array_values($options));
    }

    public function formatWithRoom($lists) {
        if (empty($lists)) {
            return array();
        }
        $serviceData = new Service_Data_Area();
        $roomLists = $serviceData->getRoomListByConds(array("id > 0"));
        if (empty($roomLists)) {
            return array();
        }

        $lists = array_column($lists, null, "id");

        foreach ($roomLists as $room) {
            if (isset($lists[$room['area_id']])) {
                $lists[$room['area_id']]['rooms'][] = $room;
            }
        }

        $options = array();
        foreach ($lists as $item) {
            $optionsItem = [
                'label' => $item['name'],
                'value' => $item['id'],
            ];
            foreach ($item['rooms'] as $room) {
                $optionsItem['children'][] = array(
                    'label' => $room['name'],
                    'value' => sprintf("%s_%s", $item["id"], $room["id"]),
                );
            }
            $options[] = $optionsItem;
        }
        return $options;
    }
}