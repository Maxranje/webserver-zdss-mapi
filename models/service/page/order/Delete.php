<?php

class Service_Page_Order_Delete extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $orderId = empty($this->request['order_id']) ? 0 : intval($this->request['order_id']);
        if ($orderId <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 订单ID不能为空");
        }

        $serviceOrder = new Service_Data_Order();
        $orderInfo = $serviceOrder->getOrderById($orderId);
        if (empty($orderInfo)) {
            throw new Zy_Core_Exception(405, "操作失败, 要删除的订单不存在");
        }

        if (!empty($orderInfo['transfer_id'])) {
            throw new Zy_Core_Exception(405, "操作失败, 结转的订单不能删除");
        }

        $serviceData  = new Service_Data_Transfer() ;
        $count = $serviceData->getTotalByConds(array('order_id' => $orderId));
        if ($count > 0) {
            throw new Zy_Core_Exception(405, "操作失败, 存在结转记录的订单不能删除");
        }

        $serviceData  = new Service_Data_Refund() ;
        $count = $serviceData->getTotalByConds(array('order_id' => $orderId));
        if ($count > 0) {
            throw new Zy_Core_Exception(405, "操作失败, 存在退款记录的订单不能删除");
        }

        $serviceData = new Service_Data_Curriculum();
        $curriculum = $serviceData->getTotalByConds(array('order_id' => $orderId));
        if ($curriculum > 0) {
            throw new Zy_Core_Exception(405, "操作失败, 已绑定课程的订单不能删除");
        }

        // 删除
        $ret = $serviceOrder->delete($orderId);
        if (!$ret) {
            throw new Zy_Core_Exception(405, "删除失败");
        }
        
        return array();
    }
}