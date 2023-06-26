<?php

class Service_Page_Student_Lists extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $pn         = empty($this->request['page']) ? 1 : intval($this->request['page']);
        $rn         = empty($this->request['perPage']) ? 20 : intval($this->request['perPage']);
        $name       = empty($this->request['name']) ? "" : strval($this->request['name']);
        $phone      = empty($this->request['phone']) ? "" : strval($this->request['phone']);
        $nickname   = empty($this->request['nickname']) ? "" : strval($this->request['nickname']);
        $isSelect   = empty($this->request['isSelect']) ? false : true;

        $pn = ($pn-1) * $rn;

        $conds = array(
            'type' => Service_Data_User_Profile::USER_TYPE_STUDENT,
        );

        if (!empty($nickname)) {
            $conds[] = "nickname like '%".$nickname."%'";
        }

        if (!empty($name)) {
            $conds[] = "name like '%".$name."%'";
        }

        if (!empty($phone)) {
            $conds[] = sprintf("phone = '%s'", $phone);
        }
        
        $serviceData = new Service_Data_User_Profile();

        $arrAppends[] = 'order by uid desc';

        if (!$isSelect) {
            $arrAppends[] = "limit {$pn} , {$rn}";
        }

        $lists = $serviceData->getListByConds($conds, array(), NULL, $arrAppends);
        if ($isSelect) {
            return $this->formatSelect ($lists);
        }

        $total = $serviceData->getTotalByConds($conds);
        return array(
            'rows' => $lists,
            'total' => $total,
        );
    }

    private function formatSelect ($lists) {
        $options = array();
        foreach ($lists as $item) {
            $options[] = array(
                'label' => sprintf("%s 【%s - %s】", $item['nickname'] , $item['school'], $item['graduate']),
                'value' => $item['uid'],
            );
        }
        $values = array();
        if(!empty($this->request['group_id'])) { 
            $serviceGroupMap = new Service_Data_User_Group();
            $miList = $serviceGroupMap->getGroupMapByGid(intval($this->request['group_id']));
            if (!empty($miList)) {
                foreach ($miList as $t) {
                    $values[]= $t['student_id'];
                }
            }
        }
        return array('options' => $options, 'value' => implode(",", $values));
    }
}