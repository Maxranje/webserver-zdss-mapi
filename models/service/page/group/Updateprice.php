<?php

class Service_Page_Group_Updateprice extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $groupId = empty($this->request['group_id']) ? 0 : intval($this->request['group_id']);
        if ($groupId <= 0) {
            throw new Zy_Core_Exception(405, "请求参数错误, 请检查");
        }

        // 查询班级
        $serviceData = new Service_Data_Group();
        $groupInfo = $serviceData->getGroupById($groupId);
        if (empty($groupInfo)) {
            throw new Zy_Core_Exception(405, "无法查到班级信息");
        }

        // 查询关联的学生
        $serviceGroupMap = new Service_Data_User_Group();
        $students = $serviceGroupMap->getGroupMapByGid($groupId);
        if (empty($students)) {
            return array();
        }

        $studentUids = array();
        foreach ($students as $item) {
            $studentUids[intval($item['student_id'])] = intval($item['student_id']);
        }
        $studentUids = array_values($studentUids);

        $studentPrice = array();
        foreach ($studentUids as $uid) {
            $suid = "su_" . $uid;
            $price = empty($this->request[$suid]) ? 0 : intval($this->request[$suid]) * 100;
            if ($price < 0 || $price == $groupInfo['price']) {
                continue;
            }
            $studentPrice[$uid] = $price;
        }

        $ret = $serviceData->editGroupStuPrice($groupId, $studentPrice);
        if ($ret === false) {
            throw new Zy_Core_Exception(405, "更新失败, 请重试");
        }
        return array();
    }
}