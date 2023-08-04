<?php

class Service_Page_Group_Create extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $name           = empty($this->request['name']) ? "" : trim($this->request['name']);
        $descs          = empty($this->request['descs']) ? "" : trim($this->request['descs']);
        $state          = empty($this->request['state']) || !in_array($this->request['state'], [Service_Data_Group::GROUP_ABLE,Service_Data_Group::GROUP_DISABLE]) ? Service_Data_Group::GROUP_ABLE : intval($this->request['state']);
        $areaOperator   = empty($this->request['area_operator']) ? 0 : intval($this->request['area_operator']);
        
        if (empty($name) || $areaOperator <= 0){
            throw new Zy_Core_Exception(405, "操作失败, 班级名称, 区域管理员不能为空");
        }

        $serviceProfile = new Service_Data_Profile();
        $userInfo = $serviceProfile->getUserInfoByUid($areaOperator);
        if (empty($userInfo) || $userInfo['state'] != Service_Data_Profile::STUDENT_ABLE) {
            throw new Zy_Core_Exception(405, "操作失败, 管理员不存在或已下线");
        }

        $serviceData = new Service_Data_Group();
        $profile = [
            "name"          => $name,
            "descs"         => $descs, 
            "state"         => $state,
            "area_operator" => $areaOperator,
            'create_time'   => time(),
            'update_time'   => time(),
        ];

        $ret = $serviceData->create($profile);
        if ($ret == false) {
            throw new Zy_Core_Exception(405, "创建失败, 请重试");
        }
        return array();
    }
}