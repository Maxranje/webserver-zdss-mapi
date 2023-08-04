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

        $serviceRecords = new Service_Data_Records();
        $recordList = $serviceRecords->getListByConds(array('schedule_id' => $id, 'state' => Service_Data_Records::RECORDS_NOMARL));
        $orderIds = Zy_Helper_Utils::arrayInt($recordList, "order_id");
        
        $orderInfos = array();
        if (!empty($orderIds)) {
            $serviceOrder = new Service_Data_Order();
            $orderInfos = $serviceOrder->getOrderByIds($orderIds);
            foreach ($orderInfos as $item) {
                if ($item['is_transfer'] == Service_Data_Order::ORDER_DONE || $item['is_refund'] == Service_Data_Order::ORDER_DONE) {
                    throw new Zy_Core_Exception(405, "操作失败, 排课中关联的订单存在结转或退款, 无法操作");
                }
            }
        }

        $profile = array(
            'orderInfos'    => $orderInfos,
            'schedule'      => $info,
            'records'       => $recordList,
        );

        $ret = $serviceSchedule->revoke($profile);
        if ($ret === false) {
            throw new Zy_Core_Exception(405, "撤销失败, 请重试");
        }
    }
}
