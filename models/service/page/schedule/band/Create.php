<?php

// 排课列表
class Service_Page_Schedule_Band_Create extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $groupId        = empty($this->request['group_id']) ? 0 : intval($this->request['group_id']);
        $orderId        = empty($this->request['order_id']) ? 0 : intval($this->request['order_id']);
        $scheduleIds    = empty($this->request['schedule_ids']) ? array() : explode(",", $this->request['schedule_ids']);
        $scheduleIds    = Zy_Helper_Utils::arrayInt($scheduleIds);

        if ($groupId <= 0 || $orderId <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 必须选定订单和班级");
        }

        // check order信息
        $serviceOrder = new Service_Data_Order();
        $orderInfo = $serviceOrder->getNmorderById($orderId);
        if (empty($orderInfo)) {
            throw new Zy_Core_Exception(405, "操作失败, 无法获取订单信息");
        }
        if ($orderInfo['balance'] <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 订单没有余额了");
        }

        // check 学生信息
        $serviceUser = new Service_Data_Profile();
        $userInfo = $serviceUser->getUserInfoByUid($orderInfo['student_uid']);
        if (empty($userInfo) || $userInfo['state'] == Service_Data_Profile::STUDENT_DISABLE) {
            throw new Zy_Core_Exception(405, "操作失败, 学员信息获取失败");
        }

        //check  group 信息
        $serviceGroup = new Service_Data_Group();
        $groupInfo = $serviceGroup->getGroupById($groupId);
        if (empty($groupInfo) || $groupInfo['state'] != Service_Data_Group::GROUP_ABLE) {
            throw new Zy_Core_Exception(405, "操作失败, 无法获取班级信息或班级已下线");
        }

        // check 科目信息
        $serviceData = new Service_Data_Subject();
        $subjectInfo = $serviceData->getSubjectByParentID(intval($orderInfo['subject_id']));
        if (empty($subjectInfo)) {
            throw new Zy_Core_Exception(405, "操作失败, 科目不存在, 无法绑定");
        }
        $subjectIds = Zy_Helper_Utils::arrayInt($subjectInfo, "id");
        
        // 学生的存量排课
        $serviceCurriculum = new Service_Data_Curriculum();
        $curriculum = $serviceCurriculum->getListByConds(array('order_id'=>$orderId));
        
        $delScheduleIds = $newScheduleIds = array();
        $duration = 0;
        foreach ($curriculum as $item) {
            if ($groupId == $item['group_id']) {
                // 没有在最新课id里, 并且已经结束, 不能删除
                if (!in_array($item['schedule_id'], $scheduleIds) && $item['state'] == Service_Data_Schedule::SCHEDULE_DONE) {
                    continue;
                }
                // 没有在最新课id里, 需要删掉
                if (!in_array($item['schedule_id'], $scheduleIds)) {
                    $delScheduleIds[] = intval($item['schedule_id']);
                }
            }
            // 所有当前已经排好的课时
            if ($item['state'] == Service_Data_Schedule::SCHEDULE_ABLE) {
                $duration += $item['end_time'] - $item['start_time'];
            }
        }

        $hasScheduleIds = Zy_Helper_Utils::arrayInt($curriculum, "schedule_id");
        $newScheduleIds = array_diff($scheduleIds, $hasScheduleIds);
        if (empty($newScheduleIds) && empty($delScheduleIds)) {
            throw new Zy_Core_Exception(405, "操作失败, 没有新增课程也没有删掉的排课");
        }

        $newSchedules = array();

        $serviceSchedule = new Service_Data_Schedule();
        // 获取新增的, 
        if (!empty($newScheduleIds)) {
            $newSchedules = $serviceSchedule->getScheduleByIds($newScheduleIds);
            if (empty($newSchedules)) {
                throw new Zy_Core_Exception(405, "操作失败, 获取新增排课信息失败");
            }
            $newSchedules = array_column($newSchedules, null, "id");
    
            foreach ($newScheduleIds as $id) {
                if (empty($newSchedules[$id])) {
                    throw new Zy_Core_Exception(405, "操作失败, 无法查询到新增排课信息, 排课ID:" . $id);
                }
                if ($newSchedules[$id]['state'] != Service_Data_Schedule::SCHEDULE_ABLE) {
                    throw new Zy_Core_Exception(405, "操作失败, 新增相关排课已经结算结束, 排课ID:" . $id);
                }
                if (!in_array($newSchedules[$id]['subject_id'] , $subjectIds)) {
                    throw new Zy_Core_Exception(405, "操作失败, 新增排课的科目与当前订单不同, 无法排课, 排课ID:" . $id);
                }
                $needDays[] = $newSchedules[$id]['start_time'];
                $needDays[] = $newSchedules[$id]['end_time'];
                $needTimes1[] = array(
                    'id'        => $id,
                    'order_id'  => $orderId,
                    'sts'       => $newSchedules[$id]['start_time'],
                    'ets'       => $newSchedules[$id]['end_time'],
                );
                $duration += $newSchedules[$id]['end_time'] - $newSchedules[$id]['start_time'];
            }

            // check 时间冲突
            $needDays = array(
                'sts' => strtotime(date('Ymd', min($needDays))),
                'ets' => strtotime(date('Ymd 23:59:59', max($needDays))),
            );

            // 判断当前这些排课中是否有order排进去, 和时间是否有冲突
            $ret = $serviceCurriculum->checkStudentTimes($needTimes1, $needDays, intval($orderInfo['student_uid']));
            if ($ret === false) {
                throw new Zy_Core_Exception(405, "操作失败, 查询学生排课冲突情况失败, 请重新提交");
            }
            if (!empty($ret)) {
                throw new Zy_Core_Exception(406, "操作失败, 学生时间有冲突, 请检查, 排课编号分别为" . implode(", ", array_column($ret, 'schedule_id')) . " 仅做参考");
            }
        }

        // 删除的排课要从预算中减掉
        if (!empty($delScheduleIds)) {
            $schedules = $serviceSchedule->getScheduleByIds($delScheduleIds);
            if (empty($schedules)) {
                throw new Zy_Core_Exception(405, "操作失败, 获取删除排课信息失败");
            }
            $schedules = array_column($schedules, null, "id");
    
            foreach ($delScheduleIds as $id) {
                if (empty($schedules[$id])) {
                    throw new Zy_Core_Exception(405, "操作失败, 无法查询到删除排课信息, 排课ID:" . $id);
                }
                if ($schedules[$id]['state'] != Service_Data_Schedule::SCHEDULE_ABLE) {
                    throw new Zy_Core_Exception(405, "操作失败, 删除相关排课已经结算结束, 排课ID:" . $id);
                }
                $duration -= $schedules[$id]['end_time'] - $schedules[$id]['start_time'];
            }
        }
        
        // 判断选定课程是否够排
        $duration = $duration / 3600;
        if ($orderInfo['balance'] < $duration * $orderInfo['price']) {
            throw new Zy_Core_Exception(405, "操作失败, 余额不足, 无法排这么多课");
        }
        
        // 创建
        $profile = array(
            'user_info'         => $userInfo,
            "order_info"        => $orderInfo,
            "newSchedules"      => $newSchedules,
            "delScheduleIds"    => $delScheduleIds,
        );
        $ret = $serviceCurriculum->create($profile);
        if (!$ret) {
            throw new Zy_Core_Exception(405, "操作失败, 绑定失败");
        }
    }

}
