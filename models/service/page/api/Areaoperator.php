<?php
// 区域管理者列表
class Service_Page_Api_Areaoperator extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $conds = array(
            sprintf('type in (%s)', implode(",",[Service_Data_Profile::USER_TYPE_ADMIN, Service_Data_Profile::USER_TYPE_TEACHER])),
        );
        
        $serviceData = new Service_Data_Profile();
        $lists = $serviceData->getListByConds($conds);
        if (empty($lists)) {
            return array();
        }

        $options = array();
        foreach ($lists as $item) {
            $optionsItem = [
                'label' => $item['nickname'],
                'value' => $item['uid'],
            ];
            $options[] = $optionsItem;
        }
        return $options;
    }
}