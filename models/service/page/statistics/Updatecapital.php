<?php

class Service_Page_Statistics_Updatecapital extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $uid            = empty($this->request['uid']) ? 0 : intval($this->request['uid']);
        $groupId        = empty($this->request['group_id']) ? 0 : intval($this->request['group_id']);
        $newExpenses    = empty($this->request['expenses']) ? 0 : intval($this->request['expenses']);
        $newExpenses    = $newExpenses * 100;

        if ($uid <=0 || $groupId <= 0) {
            throw new Zy_Core_Exception(405, "需要选定一个条目");
        }

        // 获取班级
        $serviceGroup = new Service_Data_Group();
        $groupInfo = $serviceGroup->getGroupById($groupId);
        if (empty($groupInfo)) {
            throw new Zy_Core_Exception(405, "无法找到班级信息");
        }

        // 获取学生信息
        $serviceUser = new Service_Data_User_Profile();
        $userInfo = $serviceUser->getUserInfoByUid($uid);
        if (empty($userInfo)) {
            throw new Zy_Core_Exception(405, "无法找到学生信息");
        }

        // 获取班级映射
        $serviceGroupMap = new Service_Data_User_Group();
        $groupMap = $serviceGroupMap->getListByConds(array('student_id'=>$uid, 'group_id'=>$groupId));
        if (empty($groupMap)) {
            throw new Zy_Core_Exception(405, "班级没有绑定学生");
        }

        // 获取已经消耗的
        $serviceCapital = new Service_Data_Statistics();
        $conds = array(
            'uid' => $uid, 
            "group_id" => $groupId,
        );
        $capitalList = $serviceCapital->getListByConds($conds, array("id", "category", "capital", 'ext'));
        if (empty($capitalList)) {
            throw new Zy_Core_Exception(405, "未产生消费的, 可以在班级中设置客单价, 同时在学生管理中给定存额");
        }

        // 历史消耗的钱
        $oldExpenses = 0;
        // 历史上的课时数
        $checkTimeLen = 0;
        // 历史所有课的记录
        $pkList = array();
        foreach ($capitalList as $item) {
            if (!in_array($item['category'], array(3,5))) {
                continue;
            }
            $oldExpenses += $item['capital'];
            
            // 计算已经消耗课时数
            $ext = empty($item['ext']) ? array() : json_decode($item['ext'], true);
            if (!empty($ext['job'])) {
                $item['timeLen'] = ($ext['job']['end_time'] - $ext['job']['start_time']) / 3600;
                $checkTimeLen += $item['timeLen'];
            }

            $pkList[] = $item;
        }

        if (empty($pkList)) {
            throw new Zy_Core_Exception(405, "无消耗所以无法修改金额");
        }

        if ($oldExpenses == $newExpenses) {
            throw new Zy_Core_Exception(405, "调整前结转金额一致");
        }

        if ($checkTimeLen <= 0) {
            throw new Zy_Core_Exception(405, "计算消耗时间错误, 请重试");
        }

        // 返还的价钱
        $params = array(
            "newExpenses" => $newExpenses,
            "oldExpenses" => $oldExpenses,
            "user_info" => $userInfo,
            "group_info" => $groupInfo,
            "checkTimeLen" => $checkTimeLen,
            "pkList" => $pkList,
        );

        $ret = $serviceCapital->rechargeCapital($params);
        if ($ret === false) {
            throw new Zy_Core_Exception(405, "更新失败, 请重试");
        }
        return array();
    }
}