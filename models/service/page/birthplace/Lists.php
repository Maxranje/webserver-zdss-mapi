<?php

class Service_Page_Birthplace_Lists extends Zy_Core_Service{

    private $bpid = 0;

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $pn         = empty($this->request['page']) ? 1 : intval($this->request['page']);
        $rn         = empty($this->request['perPage']) ? 20 : intval($this->request['perPage']);
        $name       = empty($this->request['subject_name']) ? "" : trim($this->request['name']);
        $isSelect   = empty($this->request['is_select']) ? false : true;

        $pn = ($pn-1) * $rn;

        $conds = array();
        if (!empty($name)) {
            $conds[] = "name like '%".$name."%'";
        }

        // 合作方单独处理
        if ($this->checkPartner()) {
            $serviceUser = new Service_Data_Profile();
            $userInfo = $serviceUser->getUserInfoByUid($this->adption['userid']);
            if (empty($userInfo['bpid'])) {
                return array();
            }
            $this->bpid =  intval($userInfo['bpid']);
            $conds['id'] = intval($userInfo['bpid']);
        }
        
        $serviceData = new Service_Data_Birthplace();

        $arrAppends[] = 'order by id';

        if (!$isSelect) {
            $arrAppends[] = "limit {$pn} , {$rn}";
        }
        
        $lists = $serviceData->getListByConds($conds, array(), NULL, $arrAppends);
        if (empty($lists)) {
            return array();
        }

        foreach ($lists as $key => $value) {
            $value['create_time'] = date("Y年m月d日", $value['create_time']);
            $value['update_time'] = date("Y年m月d日", $value['update_time']);
            $lists[$key] = $value;
        }

        if ($isSelect) {
            return $this->formatSelect($lists);
        }

        $total = $serviceData->getTotalByConds($conds);

        return array(
            'lists' => $lists,
            'total' => $total,
        );
    }

    private function formatSelect($lists) {
        $options = array();
        foreach ($lists as $item) {
            $options[] = array(
                'label' => $item['name'],
                'value' => $item['id'],
            );
        }

        $result = array('options' => array_values($options));

        if ($this->bpid > 0) {
            $result['value'] = $this->bpid;
        }
        return $result;
    }
}