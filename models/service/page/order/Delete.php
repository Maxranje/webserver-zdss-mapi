<?php

class Service_Page_Order_Delete extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $orderId    = empty($this->request['order_id']) ? 0 : intval($this->request['order_id']);
        $orderInfo  = empty($this->request['order_info']) ? "" : trim($this->request['order_info']);

        if ($orderId <= 0 || empty($orderInfo)) {
            throw new Zy_Core_Exception(405, "操作失败, 参数错误请重试");
        }

        $serviceOrder = new Service_Data_Order();
        $order = $serviceOrder->getOrderById($orderId);
        if (empty($order) || $order['balance'] > 0) {
            throw new Zy_Core_Exception(405, "操作失败, 要删除的订单不存在或还有余额, 请先结转");
        }

        $serviceData = new Service_Data_Curriculum();
        $curriculum = $serviceData->getTotalByConds(array('order_id' => $orderId));
        if ($curriculum > 0) {
            throw new Zy_Core_Exception(405, "操作失败, 已绑定课程的订单不能删除");
        }

        // 删除
        $ret = $serviceOrder->delete($orderId, $order, $orderInfo);
        if (!$ret) {
            throw new Zy_Core_Exception(405, "删除失败");
        }
        
        return array();
    }
}