<?php

// 排课列表
class Service_Page_Schedule_Band_Total extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $scheduleIds    = empty($this->request['schedule_ids']) ? array() : explode(",", $this->request['schedule_ids']);
        $scheduleIds    = Zy_Helper_Utils::arrayInt($scheduleIds);

        if (empty($scheduleIds)) {
            return array("total" => 0);
        }

        // check order信息
        $total = 0;
        $serviceData = new Service_Data_Schedule();
        $scheduleInfos = $serviceData->getScheduleByIds($scheduleIds);
        if (!empty($scheduleInfos)){
            foreach ($scheduleInfos as $item) {
                $total += floatval(sprintf("%.2f", ($item['end_time'] - $item['start_time']) / 3600));
            }
        }

        return array("total" => $total);
    }
}