<?php

class Service_Page_Group_Update extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $id             = empty($this->request['id']) ? 0 : intval($this->request['id']);
        $name           = empty($this->request['name']) ? "" : trim($this->request['name']);
        $descs          = empty($this->request['descs']) ? "" : trim($this->request['descs']);
        $claszeId       = empty($this->request['clasze_id']) ? "" : trim($this->request['clasze_id']);
        $state          = empty($this->request['state']) || !in_array($this->request['state'], [Service_Data_Group::GROUP_ABLE,Service_Data_Group::GROUP_DISABLE]) ? Service_Data_Group::GROUP_ABLE : intval($this->request['state']);
        $areaOperator   = empty($this->request['area_operator']) ? 0 : intval($this->request['area_operator']);
        
        if ($id <= 0 || empty($name) || $areaOperator <= 0 || empty($claszeId)){
            throw new Zy_Core_Exception(405, "操作失败, 班级ID, 班级名称, 助教, 班型绑定不能为空");
        }

        list($subjectId, $claszeId) = explode("_", $claszeId);
        if (intval($subjectId) <= 0 || intval($claszeId) <= 0 ) {
            throw new Zy_Core_Exception(405, "操作失败, 科目&班型选择错误");
        }

        $serviceProfile = new Service_Data_Profile();
        $userInfo = $serviceProfile->getUserInfoByUid($areaOperator);
        if (empty($userInfo) || $userInfo['state'] == Service_Data_Profile::STUDENT_DISABLE) {
            throw new Zy_Core_Exception(405, "操作失败, 助教不存在或已下线");
        }

        $serviceGroup = new Service_Data_Group();
        $groupInfo = $serviceGroup->getGroupById($id);
        if (empty($groupInfo)) {
            throw new Zy_Core_Exception(405, "操作失败, 班级信息不存在, 无法更新");
        }

        // 更新数据
        $profile = [
            "name"          => $name,
            "descs"         => $descs, 
            "state"         => $state,
            'update_time'   => time(),
        ];

        // 更新处理班型
        if ($groupInfo['subject_id'] != $subjectId || $groupInfo['cid'] != $claszeId) {
            // 判断group是否有排课
            $serviceData = new Service_Data_Schedule();
            $scheduleTotal = $serviceData->getTotalByConds(array('group_id' => $id));
            if ($scheduleTotal > 0) {
                throw new Zy_Core_Exception(405, "操作失败, 已绑定排课无法修改班型, 请删除该班级的排课");
            }

            $serviceData = new Service_Data_Subject();
            $subject = $serviceData->getSubjectById($subjectId);
            if (empty($subject)) {
                throw new Zy_Core_Exception(405, "操作失败, 绑定班型中科目不存在或被删除");
            }
    
            $serviceData = new Service_Data_Clasze();
            $clasze = $serviceData->getClaszeById($claszeId);
            if (empty($clasze)) {
                throw new Zy_Core_Exception(405, "操作失败, 绑定班型中班型不存在或被删除");
            }

            // 生成新班号
            $profile['identify'] = Zy_Helper_Utils::autoID($subject['identify'], $clasze['identify']);
            $profile['subject_id'] = $subjectId;
            $profile['cid'] = $claszeId;
        }


        // 如果助教没变, 则不更新 (更新流程会根据是否有助教来确定是否更新所有对应排课)
        if (empty($groupInfo['area_operator']) || $groupInfo['area_operator'] != $areaOperator) {
            $profile['area_operator'] = $areaOperator;
        }

        $ret = $serviceGroup->update($id, $profile);
        if ($ret == false) {
            throw new Zy_Core_Exception(405, "修改失败, 请重试");
        }
        return array();
    }
}