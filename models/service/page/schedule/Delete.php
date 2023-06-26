<?php

class Service_Page_Schedule_Delete extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $id = empty($this->request['id']) ? 0 : intval($this->request['id']);
        $ids = empty($this->request['ids']) ? "" : trim($this->request['ids']);

        if ($id <= 0 && empty($ids)) {
            throw new Zy_Core_Exception(405, "部分参数为空, 请检查");
        }

        $serviceData = new Service_Data_Schedule();

        if ($id > 0) {
            $data = $serviceData->getScheduleById($id);
            if (empty($data) || $data['state'] != 1) {
                throw new Zy_Core_Exception(405, "无法删除, 或已结算/已完成的课程不可以删除");
            }
    
            // 判断是否还有上课的map
            $status = $serviceData->deleteSchedule($id);
            if (!$status) {
                throw new Zy_Core_Exception(405, "删除错误, 请重试");
            }
        }

        if (!empty($ids)) {
            $data = $serviceData->getScheduleByIds($ids);
            $ids = array();
            foreach ($data as $item) {
                if (empty($item) || $item['state'] != 1) {
                    continue;
                }
                $ids[] = intval($item['id']);
            }

            if (empty($ids)) {
                return array();
            }
            
            $ids = implode(",", $ids);
            $status = $serviceData->deleteSchedules($ids);
            if (!$status) {
                throw new Zy_Core_Exception(405, "删除错误, 请重试");
            }
        }

        
        return array();
    }
}