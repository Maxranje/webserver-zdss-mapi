<?php

class Service_Page_Schedule_Checkout_Single extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $id             = empty($this->request['id']) ? 0 : intval($this->request['id']);
        $filterUids     = empty($this->request['filter_uids']) ? array() : explode(",", $this->request['filter_uids']);
        $filterUids     = Zy_Helper_Utils::arrayInt($filterUids);

        if ($id <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 请求参数错误");
        }

        // check 任务
        $serviceData = new Service_Data_Schedule();
        $record = $serviceData->getScheduleById($id);
        if (empty($record) || $record['state'] != Service_Data_Schedule::SCHEDULE_ABLE) {
            throw new Zy_Core_Exception(405, "操作失败, 无任务或任务已经结算完");
        }

        // 没有绑定订单, 无法结算
        $serviceCurriculum = new Service_Data_Curriculum();
        $curriculumInfos = $serviceCurriculum->getListByConds(array('schedule_id'=>$id));
        if (empty($curriculumInfos)) {
            throw new Zy_Core_Exception(405, "操作失败, 没有绑定订单无法结算, 可以删除");
        }

        // check 教师绑定获取教师收入信息
        $serviceColumn = new Service_Data_Column();
        $columnInfo = $serviceColumn->getColumnById(intval($record['column_id']));
        if (empty($columnInfo)) {
            throw new Zy_Core_Exception(405, "操作失败, 获取教师绑定信息失败");
        }

        // check 科目信息获取学生收费情况
        $serviceSubject = new Service_Data_Subject();
        $subjectInfo = $serviceSubject->getSubjectById(intval($record['subject_id']));
        if (empty($subjectInfo)) {
            throw new Zy_Core_Exception(405, "操作失败, 获取课程信息失败");
        }

        // 从订单中过滤未上课学生
        foreach ($curriculumInfos as $key => $item) {
            if (in_array($item['student_uid'], $filterUids)) {
                unset($curriculumInfos[$key]);
            }
        }
        $curriculumInfos = array_values($curriculumInfos);

        // 过滤后依然还有学生订单
        $orderInfos = array();
        if (!empty($curriculumInfos)) {
            $orderIds = Zy_Helper_Utils::arrayInt($curriculumInfos, "order_id");
            $serviceOrder = new Service_Data_Order();
            $orderInfos = $serviceOrder->getOrderByIds($orderIds);
            $orderInfos = array_column($orderInfos, null, "order_id");
        }

        $studentUids = Zy_Helper_Utils::arrayInt($curriculumInfos, "student_uid");

        $params = array(
            'schedule'          => $record,
            'column'            => $columnInfo,
            'subjectInfo'       => $subjectInfo,
            'studentUids'       => $studentUids,
            'curriculumInfos'   => $curriculumInfos,
            'orderInfos'        => $orderInfos,
        );
        
        $ret = $serviceData->checkout ($params);
        if ($ret === false) {
            throw new Zy_Core_Exception(405, "结算失败, 请重试");
        }
        return $ret;
    }
}