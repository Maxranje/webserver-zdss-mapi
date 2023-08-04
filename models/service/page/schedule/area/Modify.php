<?php

class Service_Page_Schedule_Area_Modify extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $scheduleIds = empty($this->request['ids']) ? array() : explode(",", $this->request['ids']);
        $scheduleIds = Zy_Helper_Utils::arrayInt($scheduleIds);
        
        if (empty($scheduleIds)){
            throw new Zy_Core_Exception(405, "操作错误, 需要选定随机插入的课程");
        }

        $serviceArea = new Service_Data_Area();
        $areaLists = $serviceArea->getAreaListByConds(array("id > 0"));   
        if (empty($areaLists)) {
            throw new Zy_Core_Exception(405, "操作错误, 获取校区信息失败");
        }
        $roomLists = $serviceArea->getRoomListByConds(array("id > 0"));
        if (empty($roomLists)) {
            throw new Zy_Core_Exception(405, "操作错误, 获取教室信息失败");
        }

        // 校区教室整合
        $areaLists = array_column($areaLists, null, "id");
        foreach ($roomLists as $room) {
            if (isset($areaLists[$room['area_id']])) {
                $areaLists[$room['area_id']]['rooms'][] = $room;
            }
        }

        $serviceSchedule = new Service_Data_Schedule();
        $schedules = $serviceSchedule->getScheduleByIds($scheduleIds);
        if (empty($schedules)) {
            throw new Zy_Core_Exception(405, "操作错误, 根据勾选的课程, 无法查到课程相关信息, 请检查");
        }

        // 过滤其中已结算, 排了教室,  无校区的
        $scheduleLists = $needDays = array();
        foreach ($schedules as $item) {
            if ($item['state'] != Service_Data_Schedule::SCHEDULE_ABLE) {
                continue;
            }
            if ($item['room_id'] > 0 && $item['area_id'] > 0) {
                continue;
            }
            if ($item['area_id'] <= 0) {
                continue;
            }
            if (empty($areaLists[$item['area_id']])) {
                continue;
            }
            if ($areaLists[$item['area_id']]['is_online'] == Service_Data_Area::ONLINE) {
                continue;
            }
            if (empty($areaLists[$item['area_id']]['rooms'])) {
                continue;
            }
            $item['sts'] = $item['start_time'];
            $item['ets'] = $item['end_time'];
            $key = date('Ymd', $item['start_time']);
            $scheduleLists[$key][] = $item;
            
            if (!isset($needDays[$key])) {
                $needDays[$key] = array(
                    'sts' => strtotime($key),
                    'ets' => strtotime($key) + 86400,
                );
            }
        }
        if (empty($scheduleLists)) {
            throw new Zy_Core_Exception(405, "操作错误, 检测不通过, 所选课程都已结算或排了教室或无校区");
        }
        
        $rooms = $serviceSchedule->checkRandAreaRoom($needDays, $scheduleLists, $areaLists);
        if (empty($rooms)) {
            throw new Zy_Core_Exception(405, "操作错误, 检测不通过, 所选课程都无法随机安排教室, 相关校区教室都已占用");
        }

        $param = array();
        foreach ($rooms as $scheduleId => $item) {
            $param[] = array(
                'id'        => intval($scheduleId),
                'room_id'   => intval($item['id']),
                'area_id'   => intval($item["area_id"]),
                'ext'       => "",
            );
        }

        $ret = $serviceSchedule->updateArea($param);
        if ($ret === false) {
            throw new Zy_Core_Exception(405, "更新失败, 请重试");
        }
        return array();
    }
}
