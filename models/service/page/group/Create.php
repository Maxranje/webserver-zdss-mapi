<?php

class Service_Page_Group_Create extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $name           = empty($this->request['name']) ? "" : trim($this->request['name']);
        $claszeId       = empty($this->request['clasze_id']) ? "" : trim($this->request['clasze_id']);
        $descs          = empty($this->request['descs']) ? "" : trim($this->request['descs']);
        $state          = empty($this->request['state']) || !in_array($this->request['state'], [Service_Data_Group::GROUP_ABLE,Service_Data_Group::GROUP_DISABLE]) ? Service_Data_Group::GROUP_ABLE : intval($this->request['state']);
        $areaOperator   = empty($this->request['area_operator']) ? 0 : intval($this->request['area_operator']);
        
        if (empty($name) || $areaOperator <= 0 || empty($claszeId)){
            throw new Zy_Core_Exception(405, "操作失败, 班级名称, 班型, 学管不能为空");
        }

        list($subjectId, $claszeId) = explode("_", $claszeId);
        if (intval($subjectId) <= 0 || intval($claszeId) <= 0 ) {
            throw new Zy_Core_Exception(405, "操作失败, 科目&班型选择错误");
        }

        $serviceProfile = new Service_Data_Profile();
        $userInfo = $serviceProfile->getUserInfoByUid($areaOperator);
        if (empty($userInfo) || $userInfo['state'] != Service_Data_Profile::STUDENT_ABLE) {
            throw new Zy_Core_Exception(405, "操作失败, 学管不存在或已下线");
        }

        $serviceData = new Service_Data_Subject();
        $subject = $serviceData->getSubjectById($subjectId);
        if (empty($subject)) {
            throw new Zy_Core_Exception(405, "操作失败, 科目不存在");
        }

        $serviceData = new Service_Data_Clasze();
        $clasze = $serviceData->getClaszeById($claszeId);
        if (empty($clasze)) {
            throw new Zy_Core_Exception(405, "操作失败, 班型不存在");
        }

        // 生成班号
        $identify = Zy_Helper_Utils::autoID($subject['identify'], $clasze['identify']);

        $serviceData = new Service_Data_Group();
        $profile = [
            "name"          => $name,
            "identify"      => $identify,
            "subject_id"    => $subjectId,
            "cid"           => $claszeId,
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