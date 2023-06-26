<?php

class Service_Page_Admins_Lists extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkSuper()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $pn = empty($this->request['page']) ? 1 : intval($this->request['page']);
        $rn = empty($this->request['perPage']) ? 20 : intval($this->request['perPage']);

        $pn = ($pn-1) * $rn;

        $conds = array(
            'type' => Service_Data_User_Profile::USER_TYPE_ADMIN,
        );
        
        $serviceData = new Service_Data_User_Profile();

        $arrAppends[] = 'order by create_time desc';

        $arrAppends[] = "limit {$pn} , {$rn}";

        $lists = $serviceData->getListByConds($conds, array(), NULL, $arrAppends);
        $total = $serviceData->getTotalByConds($conds);

        return array(
            'lists' => $lists,
            'total' => $total,
        );
    }
}