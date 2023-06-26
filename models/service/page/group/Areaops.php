<?php
// 区域管理者列表
class Service_Page_Group_Areaops extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $conds = array(
            'type' => Service_Data_User_Profile::USER_TYPE_ADMIN,
        );
        
        $serviceData = new Service_Data_User_Profile();
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