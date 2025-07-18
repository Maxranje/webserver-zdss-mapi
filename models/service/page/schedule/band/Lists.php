<?php

// 排课列表
class Service_Page_Schedule_Band_Lists extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $groupId    = empty($this->request['group_id']) ? 0 : intval($this->request['group_id']);
        $orderId    = empty($this->request['order_id']) ? 0 : intval($this->request['order_id']);

        if ($groupId <= 0) {
            return array();
        }

        $serviceData = new Service_Data_Group();
        $group = $serviceData->getGroupById($groupId);
        if (empty($group)) {
            throw new Zy_Core_Exception(405, "操作失败, 班级信息不存在");
        }

        $studentUid = 0;
        if ($orderId > 0) {
            $serviceData = new Service_Data_Order();
            $order = $serviceData->getOrderById($orderId);
            if (empty($order)) {
                throw new Zy_Core_Exception(405, "操作失败, 订单信息不存在, 订单下拉框中需要通过展开学生名后选定具体订单");
            }

            if ($order['subject_id'] != $group['subject_id'] || $order['cid'] != $group['cid']) {
                return array("options" => array(), "value" => "");
            }
            $studentUid = $order["student_uid"];
        }

        $conds = array(
            "state" => Service_Data_Group::GROUP_ABLE,
            "group_id" => $groupId,
        );

        $serviceData = new Service_Data_Schedule();
        $lists = $serviceData->getListByConds($conds, false, NULL, NULL);
        return $this->formatDefault($lists, $orderId, $studentUid); 
    }

    private function formatDefault ($lists, $orderId, $studentUid) {
        if (empty($lists)) {
            return array("options" => array(), "value" => "");
        }

        // 初始化参数
        $scheduleIds = Zy_Helper_Utils::arrayInt($lists, "id");
        $teacherUids = Zy_Helper_Utils::arrayInt($lists, "teacher_uid");
        $subjectIds = Zy_Helper_Utils::arrayInt($lists, "subject_id");
        $groupIds = Zy_Helper_Utils::arrayInt($lists, "group_id");
        $areaIds = Zy_Helper_Utils::arrayInt($lists, "area_id");
        $roomIds = Zy_Helper_Utils::arrayInt($lists, "room_id");

        // 获取科目信息
        $serviceCurrent = new Service_Data_Curriculum();
        $curriculum = $serviceCurrent->getListByConds(array(
            sprintf("schedule_id in (%s)", implode(",", $scheduleIds)),
            sprintf("order_id = %d", $orderId) 
        ), array("schedule_id"));
        $curriculum = Zy_Helper_Utils::arrayInt($curriculum, "schedule_id");

        // 获取学生其他学科已经绑定情况
        $diffScheduleIds = array_diff($scheduleIds, $curriculum);
        $otherCurriculum = $serviceCurrent->getListByConds(array(
            sprintf("schedule_id in (%s)", implode(",", $diffScheduleIds)),
            sprintf("student_uid = %d", $studentUid) 
        ), array("schedule_id"));
        $otherCurriculum = Zy_Helper_Utils::arrayInt($otherCurriculum, "schedule_id");
        $serviceSubject = new Service_Data_Subject();
        $subjectInfo = $serviceSubject->getSubjectByIds($subjectIds);
        $subjectInfo = array_column($subjectInfo, null, 'id');

        $subjectParentIds = Zy_Helper_Utils::arrayInt($subjectInfo, "parent_id");
        $subjectParentInfos = $serviceSubject->getSubjectByIds($subjectParentIds);
        $subjectParentInfos = array_column($subjectParentInfos, null, 'id');

        $serviceGroup = new Service_Data_Group();
        $groupInfos = $serviceGroup->getListByConds(array('id in ('.implode(",", $groupIds).')'));
        $groupInfos = array_column($groupInfos, null, 'id');

        $serviceUser = new Service_Data_Profile();
        $userInfos = $serviceUser->getListByConds(array('uid in ('.implode(',', $teacherUids).')'));
        $userInfos = array_column($userInfos, null, 'uid');

        $areaInfos = $roomInfos = array();
        if (!empty($roomIds)) {
            $serviceArea = new Service_Data_Area();
            $roomInfos = $serviceArea->getRoomListByConds(array('id in ('.implode(",", $roomIds).')'));
            $roomInfos = array_column($roomInfos, null, 'id');
        }
        if (!empty($areaIds)) {
            $serviceArea = new Service_Data_Area();
            $areaInfos = $serviceArea->getAreaListByConds(array('id in ('.implode(",", $areaIds).')'));
            $areaInfos = array_column($areaInfos, null, 'id');
        }
        
        $result = array();
        foreach ($lists as $key => $item) {
            if (empty($userInfos[$item['teacher_uid']]['nickname'])){
                continue;
            }
            if (empty($subjectInfo[$item['subject_id']]['name'])) {
                continue;
            }
            if (empty($subjectInfo[$item['subject_id']]['parent_id'])) {
                continue;
            }
            $subjectParentId = $subjectInfo[$item['subject_id']]['parent_id'];
            if (empty($subjectParentInfos[$subjectParentId]['name'])) {
                continue;
            }
            if (empty($groupInfos[$item['group_id']]['name'])) {
                continue;
            }

            // 校区信息
            $areaName = $roomName = "无教室";
            if (!empty($item['area_id']) && !empty($areaInfos[$item['area_id']]['name'])) {
                $areaName = $areaInfos[$item['area_id']]['name'];
                if (!empty($item['room_id']) && !empty($roomInfos[$item['room_id']]['name'])) {
                    $roomName = $roomInfos[$item['room_id']]['name'];
                }
                $areaName = sprintf("%s-%s", $areaName, $roomName);
                $ext = empty($item['ext']) ? array() : json_decode($item['ext'], true);
                if (isset($ext['is_online']) && $ext['is_online'] == 1) {
                    $areaName .= "(线上)";
                }
            }

            $state = 0;
            if (in_array($item['id'], $curriculum) || in_array($item['id'], $otherCurriculum)) {
                $state = 1;
            }
            
            $result[] = array(
                "id"                    => intval($item['id']),
                "area_name"             => $areaName,
                "duration"              => sprintf("%.2f小时",  ($item['end_time'] - $item['start_time']) / 3600),
                "label"                 => date('y年m月d日 H:i', $item['start_time']) . "~".date('H:i', $item['end_time']),
                "value"                 => intval($item['id']),
                "teacher_subject_name"  => sprintf("%s(%s / %s)", $userInfos[$item['teacher_uid']]['nickname'], $subjectParentInfos[$subjectParentId]['name'], $subjectInfo[$item['subject_id']]['name']),
                "band_state"            => $state,
            );
	    }
        $values = implode(",", $curriculum);
        return array('options' => $result, 'value' => $values);
    }
}
