<?php

class Service_Page_Schedule_Checkout extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $id = empty($this->request['id']) ? 0 : intval($this->request['id']);
        if ($id <= 0) {
            throw new Zy_Core_Exception(405, "请求参数错误");
        }

        $serviceData = new Service_Data_Schedule();
        $record = $serviceData->getScheduleById($id);
        if (empty($record)) {
            throw new Zy_Core_Exception(405, "无任务");
        }

        // 只有未结算的进行处理
        if ($record['state'] != 1) {
            throw new Zy_Core_Exception(405, "任务已经结算完");
        }
        
        $params = $this->getMapInformation($record);
        $ret = $serviceData->checkJobs ($params);

        return $ret;
    }

    private function getMapInformation($record) {
        $serviceColumn = new Service_Data_Column();
        $columnInfo = $serviceColumn->getColumnById(intval($record['column_id']));

        $serviceGroupMap = new Service_Data_User_Group();
        $groupMapInfos = $serviceGroupMap->getGroupMapByGid(intval($record['group_id']));
        $studentUids = array_column($groupMapInfos, 'student_id');

        $serviceStudent = new Service_Data_User_Profile();
        $studentInfos = $serviceStudent->getUserInfoByUids($studentUids);
        $studentInfos = array_column($studentInfos, null, "uid");

        $serviceGroup = new Service_Data_Group();
        $groupInfo = $serviceGroup->getGroupById(intval($record['group_id']));


        $params = array(
            'job' => $record,
            'column' => $columnInfo,
            'group' => $groupInfo,
            'studentUids' => $studentUids,
            'studentInfos' => $studentInfos,
        );
        
        return $params;
    }

}