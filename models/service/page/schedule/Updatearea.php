<?php

class Service_Page_Schedule_Updatearea extends Zy_Core_Service{

    public $serviceSchedule;

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限");
        }

        $id     = empty($this->request['id']) ? 0 : intval($this->request['id']);
        $areaId = empty($this->request['a_r_id']) ? "" : $this->request['a_r_id'];
        $isOnline = empty($this->request['is_online']) ? 0 : 1;
        if ($id <= 0){
            throw new Zy_Core_Exception(405, "请求参数错误");
        }

        $roomId = 0;
        if (!empty($areaId)) {
            if (strpos($areaId, "_") === false) {
                throw new Zy_Core_Exception(405, "校区教室必须都选, 或者都删掉才能保存");
            }
            list($areaId, $roomId) = explode("_", $areaId);
            if ($roomId <= 0 || $areaId <= 0){
                throw new Zy_Core_Exception(405, "校区教室必须都选, 或者都删掉才能保存");
            }    
        }

        // 查询当前排课信息
        $serviceSchedule = new Service_Data_Schedule();
        $schedule = $serviceSchedule->getScheduleById($id);
        if (empty($schedule)) {
            throw new Zy_Core_Exception(405, "无法找到当前排课, 提供排课编号为: " . $id);   
        }
        
        // 不允许只配置一个值, 要有都有
        $ext = empty($schedule['ext']) ? array() : json_decode($schedule['ext'], true);
        if ($roomId <= 0 || $areaId <= 0) {
            $roomId = $areaId = 0;
            unset($ext['is_online']);
        } else if ($areaId == 3 && $roomId == 15){ // 彻底线上
            unset($ext['is_online']);
        } else {
            $this->checkArea($schedule, $id, $areaId, $roomId);
            $ext['is_online'] = $isOnline;
        }

        $param = array(
            'id'      => $id,
            'room_id' => $roomId,
            'area_id' => $areaId,
            'ext' => json_encode($ext),
        );

        $serviceSchedule = new Service_Data_Schedule();        
        $ret = $serviceSchedule->updateArea($param);

        if ($ret === false) {
            throw new Zy_Core_Exception(405, "更新失败, 请重试");
        }
        return array();
    }

    public function checkArea($schedule, $id, $areaId, $roomId) {
        // 查询这个天, 这个校区和教室是否存在,
        $sts = strtotime(date("Ymd 00:00:00", $schedule['start_time']));
        $ets = strtotime(date("Ymd 23:59:59", $schedule['end_time']));
        $conds = array(
            'start_time >= ' . $sts,
            "end_time <= " . $ets,
            "area_id" => $areaId,
            "room_id" => $roomId,
            "state" => 1,
        );
        $serviceSchedule = new Service_Data_Schedule();
        $scLists = $serviceSchedule->getListByConds($conds);
        if (empty($scLists)) {
            return ;
        }

        // 重新定义
        $sts = $schedule['start_time'];
        $ets = $schedule['end_time'];

        $match = array();
        foreach ($scLists as $k => $t) {
            // 比较, 开始时间大于存开始时间,  结束时间小于存结束时间
            if ($t['id'] == $id) {
                continue;
            }
            if ($t['start_time'] > $sts && $t['start_time'] < $ets) {
                $match = $t;
                break;
            }
            if ($t['end_time'] > $sts && $t['end_time'] < $ets) {
                $match = $t;
                break;
            }
            if ($t['start_time'] < $sts && $t['end_time'] > $ets) {
                $match = $t;
                break;
            }
            if ($t['start_time'] == $sts || $t['end_time'] == $ets) {
                $match = $t;
                break;
            }
        }
        
        if (!empty($match)) {
            $serverUser = new Service_Data_User_Profile();
            $tinfo = $serverUser->getUserInfoByUid(intval($match['teacher_id']));
            if (empty($tinfo['nickname'])) {
                throw new Zy_Core_Exception(405, "教室被占, 目前由于无法查询被占教师信息, 提供排课编号为: " . $match['id']);   
            }

            $serverGroup = new Service_Data_Group();
            $ginfo = $serverGroup->getGroupById(intval($match['group_id']));
            if (empty($ginfo['name'])) {
                throw new Zy_Core_Exception(405, "教室被占, 目前由于无法查询被占班级信息, 提供排课编号为: " . $match['id']);   
            }

            $msg = sprintf("教室被占, 排课编号为:%s, 教师:%s, 班级:%s", $match['id'], $tinfo['nickname'], $ginfo['name']);
            throw new Zy_Core_Exception(405, $msg);   
        }

        return ;
    }

}
