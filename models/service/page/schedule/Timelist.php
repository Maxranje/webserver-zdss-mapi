<?php

class Service_Page_Schedule_Timelist extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $type       = empty($this->request['type']) ? 0 : intval($this->request['type']);
        $week       = empty($this->request['week']) ? "" : $this->request['week'];
        $week       = empty($week) ? array() : explode(",", $week);
        $length     = empty($this->request['length']) ? 0 : intval($this->request['length']);
        $startDay   = empty($this->request['start_day']) ? 0 : intval($this->request['start_day']);
        $timeRange  = empty($this->request['time_range']) ? "" : strval($this->request['time_range']);
        $isSimple   = empty($this->request['is_simple']) ? false : true;

        if (!in_array($type, array(1,2))){
            throw new Zy_Core_Exception(405, "操作失败, 每周/隔周必须选一个");
        }

        if (empty($week) || !empty(array_diff($week, array("1","2","3","4","5","6","7")))){
            throw new Zy_Core_Exception(405, "操作失败, 必须选择周几");
        }

        if ($length <= 0 || $length >20){
            throw new Zy_Core_Exception(405, "操作失败, 课时长度必须在20内且大于0");
        }

        if ($startDay <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 起始时间不能为空");
        }

        if (count($week) > $length) {
            throw new Zy_Core_Exception(405, "操作失败, 配置中一周所上的课时不能大于总课时数, 请检查");
        }

        $timeRange = empty($timeRange) ? array() : explode(",", $timeRange);
        if (empty($timeRange) || count($timeRange) != 2) {
            throw new Zy_Core_Exception(405, "操作失败, 必须设置模板时间");
        }

        $needTimes = array();
        foreach ($timeRange as $item) {
            $range = explode(":", $item);
            if (empty($range) || count($range) != 2) {
                throw new Zy_Core_Exception(405, "操作失败, 模板时间必须都要配置并且时间格式不能有错");
            }
            $needTimes[] = ($range[0] * 3600) + ($range[1] * 60);
        }

        if (empty($needTimes)) {
            throw new Zy_Core_Exception(405, "操作失败, 模板时间不正确, 请检查");
        }

        $needTimes = array(
            'sts' => min($needTimes),
            'ets' => max($needTimes),
        );

        // 5分钟到4小时
        if ($needTimes['sts'] >= $needTimes['ets'] 
            || $needTimes['ets'] - $needTimes['sts'] > (4 * 3600)
            || $needTimes['ets'] - $needTimes['sts'] < 300) {
            throw new Zy_Core_Exception(405, "操作失败, 模板时间必须在5分钟到4小时之间");
        }

        // 计算具体时间
        $needTimes = $this->initParamsTime($needTimes, $startDay, $type, $week, $length) ;

        $serviceData = new Service_Data_Schedule();
        $ret = $serviceData->checkParamsTime($needTimes) ;
        if (!$ret) {
            throw new Zy_Core_Exception(405, "操作失败, 保存的时间有冲突, 请查询后在配置");
        }

        if ($isSimple) {
            return $this->formatSimple($needTimes);
        }
        
        return  $this->formatBase($needTimes);
    }

    private function initParamsTime ($needTimes, $startDay, $type, $week, $length) {
        $result = array();
        $typeTime = $type * 7 * 86400;
        $startWeekDay = strtotime("next Monday", $startDay) - 7 * 86400; // 本周第一天
        sort($week);
        $i = 0;
        while ($i < $length){
            foreach ($week as $w) {
                $sts = ($w - 1) * 86400 + $startWeekDay + $needTimes['sts'];
                $ets = ($w - 1) * 86400 + $startWeekDay + $needTimes['ets'];
                if ($sts <= time()) {
                    continue;
                }
                $result[] = array(
                    'sts' => $sts,
                    'ets' => $ets,
                );
                $i++;
                if ($i >= $length) {
                    break;
                }
            }
            $startWeekDay += $typeTime;
        }
        return $result;
    }

    private function formatBase ($needTimes) {
        $result = array(
            "type"=> "combo",
            "name"=> "times" . rand(1, 1000000),
            "multiple"=> true,
            "items"=> [
                array(
                    "type"=> "input-date",
                    "name"=> "date",
                    "onlyLeaf"=>true
                ),
                array(
                    "type"=> "input-time-range",
                    "format"=>"HH:mm",
                    "label" => "模板时间",
                    "name"=> "time_range",
                )
            ],
            "value" => array(),
        );

        foreach ($needTimes as $time) {
            $v = array(
                'date' => strtotime(date('Ymd', $time['sts'])),
                'time_range' => $this->request['time_range'],
            );
            $result['value'][] = $v;
        }
        return $result;
    }

    private function formatSimple ($needTimes) {
        $result = array();
        foreach ($needTimes as $time) {
            $v = array(
                'date' => strtotime(date('Ymd', $time['sts'])),
                'time_range' => $this->request['time_range'],
            );
            $result[] = $v;
        }
        return $result;
    }

}