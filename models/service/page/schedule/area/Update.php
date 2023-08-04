<?php

class Service_Page_Schedule_Area_Update extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限");
        }

        $id         = empty($this->request['id']) ? 0 : intval($this->request['id']);
        $areaId     = empty($this->request['a_r_id']) ? "" : $this->request['a_r_id'];
        $isOnline   = empty($this->request['is_online']) ? false : true;

        if ($id <= 0){
            throw new Zy_Core_Exception(405, "操作错误, 请求参数错误");
        }

        $areaInfo = $roomInfo = array();
        if (!empty($areaId)) {
            if (strpos($areaId, "_") === false) {
                throw new Zy_Core_Exception(405, "操作错误, 校区教室必须都选, 或者都删掉才能保存");
            }
            list($areaId, $roomId) = explode("_", $areaId);
            if ($roomId <= 0 || $areaId <= 0){
                throw new Zy_Core_Exception(405, "操作错误, 校区教室必须都选, 或者都删掉才能保存");
            }    

            $serviceData = new Service_Data_Area();
            $areaInfo = $serviceData->getAreaById(intval($areaId));
            if (empty($areaInfo)) {
                throw new Zy_Core_Exception(405, "操作错误, 无法查询校区信息");
            }
            $roomInfo = $serviceData->getAreaRoomById(intval($areaId), intval($roomId));
            if (empty($roomInfo)) {
                throw new Zy_Core_Exception(405, "操作错误, 无法查询教室信息");
            }
        } else {
            $areaId = $roomId = 0;
        }

        // 查询当前排课信息
        $serviceSchedule = new Service_Data_Schedule();
        $schedule = $serviceSchedule->getScheduleById($id);
        if (empty($schedule) || $schedule['state'] == Service_Data_Schedule::SCHEDULE_DONE) {
            throw new Zy_Core_Exception(405, "操作错误, 无法找到当前排课或已结算, 提供排课编号为: " . $id);   
        }
        
        // 不允许只配置一个值, 要有都有
        $ext = empty($schedule['ext']) ? array() : json_decode($schedule['ext'], true);
        if ($roomId <= 0 || $areaId <= 0) {
            $roomId = $areaId = 0;
            unset($ext['is_online']);
        } else if (isset($areaInfo['is_online']) && $areaInfo['is_online'] == Service_Data_Area::ONLINE){ // 彻底线上
            unset($ext['is_online']);
        } else if ($roomId > 0 || $areaId > 0){
            $needDays = array(
                'sts' => strtotime(date("Ymd 00:00:00", $schedule['start_time'])),
                'ets' => strtotime(date("Ymd 23:59:59", $schedule['end_time']))
            );
            $needTims = array(array(
                'sts' => $schedule['start_time'],
                'ets' => $schedule['end_time'],
            ));
            $schedule['room_id'] = $roomId;
            $schedule['area_id'] = $areaId;
            $ret = $serviceSchedule->checkRoom($needTims, $needDays, $schedule);
            if ($ret === false) {
                throw new Zy_Core_Exception(405, "操作失败, 查询排课教室冲突情况失败, 请重新提交");
            }
            if (!empty($ret)) {
                throw new Zy_Core_Exception(406, "操作失败, 教室时间有冲突, 请检查班级时间, 排课编号分别为" . implode(", ", array_column($ret, 'id')). " 仅做参考");
            }
            $ext['is_online'] = $isOnline;
        }

        $param = array(
            'id'        => $id,
            'room_id'   => $roomId,
            'area_id'   => $areaId,
            'ext'       => json_encode($ext),
        );
   
        $ret = $serviceSchedule->updateArea(array($param));

        if ($ret === false) {
            throw new Zy_Core_Exception(405, "更新失败, 请重试");
        }
        return array();
    }
}
