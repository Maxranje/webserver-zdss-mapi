<?php

class Service_Page_Schedule_Revoke extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkSuper()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $id = empty($this->request['id']) ? 0 : intval($this->request['id']);

        if ($id <= 0){
            throw new Zy_Core_Exception(405, "操作失败, 请求参数错误");
        }

        $serviceSchedule = new Service_Data_Schedule();
        $info = $serviceSchedule->getScheduleById($id);
        if (empty($info) || $info['state'] != Service_Data_Schedule::SCHEDULE_DONE) {
            throw new Zy_Core_Exception(405, "操作失败, 排课记录查询失败或该记录未结算");
        }

        $serviceCurriculum  = new Service_Data_Curriculum();
        $curriculumList = $serviceCurriculum->getListByConds(array('schedule_id' => $id));
        $orderIds = Zy_Helper_Utils::arrayInt($curriculumList, "order_id");

        $serviceRecords = new Service_Data_Records();
        $recordList = $serviceRecords->getListByConds(array('schedule_id' => $id, 'state' => Service_Data_Records::RECORDS_NOMARL));
        

        $profile = array(
            'orderids'      => $orderIds,
            'schedule'      => $info,
            'records'       => $recordList,
        );

        $ret = $serviceSchedule->revoke($profile);
        if ($ret === false) {
            throw new Zy_Core_Exception(405, "撤销失败, 请重试");
        }
    }
}
