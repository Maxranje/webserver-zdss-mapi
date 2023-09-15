<?php

class Service_Page_Schedule_Delete extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin() || !$this->isModeAble(Service_Data_Roles::ROLE_MODE_SCHEDULE_DELETE)) {
            throw new Zy_Core_Exception(405, "无权限操作");
        }

        $id = empty($this->request['id']) ? 0 : intval($this->request['id']);
        $ids = empty($this->request['ids']) ? "" : trim($this->request['ids']);

        if ($id <= 0 && empty($ids)) {
            throw new Zy_Core_Exception(405, "操作失败, 部分参数为空, 请检查");
        }

        $serviceData = new Service_Data_Schedule();

        if ($id > 0) {
            $data = $serviceData->getScheduleById($id);
            if (empty($data) || $data['state'] != Service_Data_Schedule::SCHEDULE_ABLE) {
                throw new Zy_Core_Exception(405, "操作失败, 或已结算/已完成的课程不可以删除");
            }

            // 判断是否还有上课的map
            $status = $serviceData->deleteSchedules($id);
            if (!$status) {
                throw new Zy_Core_Exception(405, "删除错误, 请重试");
            }
        }

        if (!empty($ids)) {
            $data = $serviceData->getScheduleByIds($ids);
            $ids = array();
            foreach ($data as $item) {
                if (empty($item) || $item['state'] != Service_Data_Schedule::SCHEDULE_ABLE) {
                    throw new Zy_Core_Exception(405, "操作失败, 或已结算/已完成的课程不可以删除");
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