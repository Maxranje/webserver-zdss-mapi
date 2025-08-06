<?php

class Service_Page_Napi_Calendar_Typelists extends Zy_Core_Service{

    public function execute (){
        
        if (!$this->checkAdmin() && !$this->checkTeacherPages()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $type = empty($this->request['type']) ? "" : trim($this->request['type']);

        if (!in_array($type, array("group", "student", "teacher"))) {
            throw new Zy_Core_Exception(405, "操作失败, 类目不正确");
        }

        $lists = array();
        if ($type == "student") {
            $lists = $this->getStudentList();
        } else if ($type == "group") {
            $lists = $this->getGroupList();
        } else {
            $lists = $this->getTeacherList();
        }
        if (empty($lists)) {
            return array();
        }
        return array("list" => $lists);
    }

    private function getStudentList() {
        
        $serviceData = new Service_Data_Profile();

        $arrAppends = array(
            'order by uid desc',
            'limit 0,400',
        );

        $conds = array(
            "type = 12"
        );
        $lists = $serviceData->getListByConds($conds, array(
            "uid",
            "nickname"
        ), NULL, $arrAppends);
        if (empty($lists)) {
            return array();
        }

        $result = array();
        foreach ($lists as $v) {
            $result[] = array(
                'id' => $v["uid"],
                'name' => $v["nickname"],
            );
        }
        return $result;
    }

    private function getTeacherList() {
        
        $serviceData = new Service_Data_Profile();

        $arrAppends = array(
            'order by uid desc',
        );

        $conds = array(
            "type = 13"
        );
        $lists = $serviceData->getListByConds($conds, array(
            "uid",
            "nickname"
        ), NULL, $arrAppends);
        if (empty($lists)) {
            return array();
        }

        $result = array();
        foreach ($lists as $v) {
            $result[] = array(
                'id' => $v["uid"],
                'name' => $v["nickname"],
            );
        }
        return $result;
    }

    private function getGroupList() {
        
        $serviceData = new Service_Data_Group();

        $arrAppends = array(
            'order by id desc',
        );

        $conds = array(
            "state = 1",
        );

        $lists = $serviceData->getListByConds($conds, array(
            "id",
            "name"
        ), NULL, $arrAppends);
        if (empty($lists)) {
            return array();
        }
        return $lists;
    }    
}