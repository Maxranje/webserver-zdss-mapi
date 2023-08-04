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

        if ($groupId <= 0 || $orderId <= 0 || empty($scheduleIds)) {
            throw new Zy_Core_Exception(405, "操作失败, 必须选定订单和具体排课以及班级");
        }

        // check order信息
        $serviceOrder = new Service_Data_Order();
        $orderInfo = $serviceOrder->getOrderById($orderId);
        if (empty($orderInfo) || $orderInfo['is_transfer'] == Service_Data_Order::ORDER_DONE || $orderInfo['is_refund'] == Service_Data_Order::ORDER_DONE) {
            throw new Zy_Core_Exception(405, "操作失败, 无法获取订单信息或订单已结转或退款");
        }

        //check  group 信息
        $serviceGroup = new Service_Data_Group();
        $groupInfo = $serviceGroup->getGroupById($groupId);
        if (empty($groupInfo) || $groupInfo['state'] != Service_Data_Group::GROUP_ABLE) {
            throw new Zy_Core_Exception(405, "操作失败, 无法获取班级信息或班级已下线");
        }

        // check 科目信息
        $serviceData = new Service_Data_Subject();
        $subjectInfo = $serviceData->getSubjectById(intval($orderInfo['subject_id']));
        if (empty($subjectInfo)) {
            throw new Zy_Core_Exception(405, "操作失败, 科目不存在, 无法绑定");
        }

        // 学生的存量排课
        $serviceCurriculum = new Service_Data_Curriculum();
        $curriculum = $serviceCurriculum->getListByConds(array('order_id'=>$orderId));
        
        $delScheduleIds = $newScheduleIds = array();
        $duration = 0;
        foreach ($curriculum as $item) {
            if ($groupId == $item['group_id']) {
                // 没有在最新课id里, 并且已经结束, 不能删除
                if (!in_array($item['schedule_id'], $scheduleIds) && $item['state'] == Service_Data_Schedule::SCHEDULE_DONE) {
                    throw new Zy_Core_Exception(405, "操作失败, 原有相关排课已经结算结束, 不能取消勾选, 排课ID:" . $item['schedule_id']);
                }
                // 没有在最新课id里, 需要删掉
                if (!in_array($item['schedule_id'], $scheduleIds)) {
                    $delScheduleIds[] = intval($item['schedule_id']);
                }
            }
            if ($item['state'] == Service_Data_Schedule::SCHEDULE_ABLE) {
                $duration += $item['end_time'] - $item['start_time'];
            }
        }

        $hasScheduleIds = Zy_Helper_Utils::arrayInt($curriculum, "schedule_id");
        $newScheduleIds = array_diff($scheduleIds, $hasScheduleIds);
        if (empty($newScheduleIds)) {
            throw new Zy_Core_Exception(405, "操作失败, 没有新增课程, 所以已选择课程都已经排好");
        }

        // 获取新增的, 
        $serviceSchedule = new Service_Data_Schedule();
        $schedules = $serviceSchedule->getScheduleByIds($newScheduleIds);
        if (empty($schedules)) {
            throw new Zy_Core_Exception(405, "操作失败, 获取排课信息失败");
        }
        $schedules = array_column($schedules, null, "id");

        foreach ($newScheduleIds as $id) {
            if (empty($schedules[$id])) {
                throw new Zy_Core_Exception(405, "操作失败, 无法查询到新增排课信息, 排课ID:" . $id);
            }
            if ($schedules[$id]['state'] != Service_Data_Schedule::SCHEDULE_ABLE) {
                throw new Zy_Core_Exception(405, "操作失败, 新增相关排课已经结算结束, 排课ID:" . $id);
            }
            if ($schedules[$id]['subject_id'] != $subjectInfo['id']) {
                throw new Zy_Core_Exception(405, "操作失败, 新增排课的科目与当前订单不同, 无法排课, 排课ID:" . $id);
            }
            $needDays[] = $schedules[$id]['start_time'];
            $needDays[] = $schedules[$id]['end_time'];
            $needTimes1[] = array(
                'id'    => $id,
                'sts'   => $schedules[$id]['start_time'],
                'ets'   => $schedules[$id]['end_time'],
            );
            $duration += $schedules[$id]['end_time'] - $schedules[$id]['start_time'];
        }

        // check 优惠信息
        $discountPrice = 0;
        if ($orderInfo['discount_type'] == Service_Data_Order::DISCOUNT_Z) {
            $discountPrice = (100 - intval($orderInfo['discount'])) / 100 *  intval($subjectInfo['price']);
        } else if ($orderInfo['discount_type'] == Service_Data_Order::DISCOUNT_J) {
            $discountPrice = intval($orderInfo['discount']);
        }

        // 判断选定课程是否够排
        $duration = $duration / 3600;
        if ($orderInfo['balance'] < $duration * (intval($subjectInfo['price']) - $discountPrice)) {
            throw new Zy_Core_Exception(405, "操作失败, 余额不足, 无法排这么多课");
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
            throw new Zy_Core_Exception(406, "操作失败, 学生时间有冲突, 请检查, 排课编号分别为" . implode(", ", array_column($ret, 'id')) . " 仅做参考");
        }

        // 创建
        $profile = array(
            "order_info"        => $orderInfo,
            "schedules"         => $schedules,
            "delScheduleIds"    => $delScheduleIds,
        );
        $ret = $serviceCurriculum->create($profile);
        if (!$ret) {
            throw new Zy_Core_Exception(405, "操作失败, 绑定失败");
        }
    }

}
