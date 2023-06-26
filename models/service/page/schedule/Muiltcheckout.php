<?php

class Service_Page_Schedule_Muiltcheckout extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $id     = empty($this->request['id']) ? 0 : intval($this->request['id']);
        $uids   = empty($this->request['uids']) ? "" : strval($this->request['uids']);
        if ($id <= 0) {
            throw new Zy_Core_Exception(405, "请求参数错误, 需要选定课程");
        }
        
        // 处理uids, 并且每一个值都是int
        $uids = empty($uids) ? array(): explode(",", $uids);
        if (!empty($uids)) {
            foreach ($uids as $key => $value) {
                $uids[$key] = intval($value);
            }
            $uids = array_values($uids);
        }

        $serviceData = new Service_Data_Schedule();
        $record = $serviceData->getScheduleById($id);
        if (empty($record)) {
            throw new Zy_Core_Exception(405, "当前记录不需要结算, 无结算id");
        }

        // 只有未结算的进行处理
        if ($record['state'] != 1) {
            throw new Zy_Core_Exception(405, "任务已经结算完");
        }
        
        $params = $this->getMapInformation($record, $uids);
        $ret = $serviceData->checkJobs ($params);

        return $ret;
    }

    private function getMapInformation($record, $uids) {
        $serviceColumn = new Service_Data_Column();
        $columnInfo = $serviceColumn->getColumnById(intval($record['column_id']));

        $serviceGroupMap = new Service_Data_User_Group();
        $groupMapInfos = $serviceGroupMap->getGroupMapByGid(intval($record['group_id']));
        $studentUids = array_column($groupMapInfos, 'student_id');

        // 如果存在要剔除的学生, 先check是否是全部
        $diff = array_diff($studentUids, $uids);
        if (!empty($uids) && empty($diff)) {
            throw new Zy_Core_Exception(405, "不能剔除所有学生, 如果都剔除请删掉该次排课");
        }

        $serviceStudent = new Service_Data_User_Profile();
        $studentInfos = $serviceStudent->getUserInfoByUids($diff);
        $studentInfos = array_column($studentInfos, null, "uid");

        $serviceGroup = new Service_Data_Group();
        $groupInfo = $serviceGroup->getGroupById(intval($record['group_id']));


        $params = array(
            'job' => $record,
            'column' => $columnInfo,
            'group' => $groupInfo,
            'studentUids' => $diff,
            'studentInfos' => $studentInfos,
        );
        
        return $params;
    }

}