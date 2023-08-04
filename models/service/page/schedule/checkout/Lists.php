<?php

class Service_Page_Schedule_Checkout_Lists extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $id = empty($this->request['id']) ? 0 : intval($this->request['id']);
        if ($id <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 请求参数错误");
        }

        $serviceData = new Service_Data_Schedule();
        $record = $serviceData->getScheduleById($id);
        if (empty($record) || $record['state'] != Service_Data_Schedule::SCHEDULE_ABLE) {
            throw new Zy_Core_Exception(405, "操作失败, 课程不存在或已结束");
        }

        $serviceCurriculum = new Service_Data_Curriculum();
        $curriculumInfos = $serviceCurriculum->getListByConds(array('schedule_id'=>$id));
        if (empty($curriculumInfos)) {
            throw new Zy_Core_Exception(405, "操作失败, 无绑定订单信息");
        }

        $studentUids = Zy_Helper_Utils::arrayInt($curriculumInfos, "student_uid");
        $serviceStudent = new Service_Data_Profile();
        $studentInfos = $serviceStudent->getUserInfoByUids($studentUids);
        $studentInfos = array_column($studentInfos, null, "uid");
        if (empty($studentInfos)) {
            throw new Zy_Core_Exception(405, "操作失败, 学生信息获取失败");
        }

        return $this->formatSelect($studentInfos);
    }

    private function formatSelect($lists) {
        if (empty($lists)) {
            return array();
        }
        
        $optionsTmp = array();
        foreach ($lists as $item) {
            $optionsTmp[$item['uid']][] = array(
                'label' => $item['nickname'],
                'value' => $item['uid'],
            );
        }
        $options = array();
        foreach ($optionsTmp as $item) {
            if (empty($item)) {
                continue;
            }
            foreach ($item as $v) {
                $options[] = $v;
            }
        }
        return array('options' => array_values($options));
    }
}