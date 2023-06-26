<?php

// 只有校区
class Service_Page_Area_Onlyarea extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $serviceData = new Service_Data_Area();
        $lists = $serviceData->getList();   
        if (empty($lists)) {
            return array();
        }

        $options = array();
        foreach ($lists as $item) {
            $optionsItem = [
                'label' => $item['name'],
                'value' => $item['id'],
            ];
            $options[] = $optionsItem;
        }
        return $options;
    }
}