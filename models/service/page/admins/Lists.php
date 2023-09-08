<?php

class Service_Page_Admins_Lists extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkSuper()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $nickname   = empty($this->request['nickname']) ? "" : trim($this->request['nickname']);
        $type       = empty($this->request['type']) ? 0 : intval($this->request['type']);
        $pn         = empty($this->request['page']) ? 1 : intval($this->request['page']);
        $rn         = empty($this->request['perPage']) ? 20 : intval($this->request['perPage']);

        $pn = ($pn-1) * $rn;

        $conds = array();

        if (!empty($nickname)) {
            $conds[] = "nickname like '%".$nickname."%'";
        }

        if ($type == 0) {
            $conds[] = sprintf("type in (%s)", implode(",", array(Service_Data_Profile::USER_TYPE_ADMIN, Service_Data_Profile::USER_TYPE_PARTNER)));
        } else {
            $conds[] = sprintf("type = %d", $type);
        }
        
        $serviceData = new Service_Data_Profile();

        $arrAppends[] = 'order by create_time desc';

        $arrAppends[] = "limit {$pn} , {$rn}";

        $lists = $serviceData->getListByConds($conds, array(), NULL, $arrAppends);
        if (empty($lists)) {
            return array();
        }
        $lists = $this->formatDefault($lists);
        $total = $serviceData->getTotalByConds($conds);

        return array(
            'lists' => $lists,
            'total' => $total,
        );
    }

    public function formatDefault ($lists) {

        $bpids = Zy_Helper_Utils::arrayInt($lists, "bpid");
        $serviceData = new Service_Data_Birthplace();
        $birthplaces = $serviceData->getBirthplaceByIds($bpids);
        $birthplaces = array_column($birthplaces, null, "id");

        foreach ($lists as $key => $value) {
            $value['create_time'] = date("Y年m月d日", $value['create_time']);
            $value['update_time'] = date("Y年m月d日", $value['update_time']);
            $value['birthplace']  = empty($birthplaces[$value['bpid']]['name']) ? "" : $birthplaces[$value['bpid']]['name'];
            $lists[$key] = $value;
        }

        return $lists;
    }
}