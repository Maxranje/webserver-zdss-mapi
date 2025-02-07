<?php

class Service_Data_Order {

    private $daoOrder ;

    const WARNING_BALANCE = 500000; // 分为单位

    // 订单类型
    const ORDER_TYPE_NORMAL     = 1; // 普通订单
    const ORDER_TYPE_ABROADPLAN = 2; // 计划订单

    public function __construct() {
        $this->daoOrder = new Dao_Order () ;
    }

    // 根据id获取订单
    public function getOrderById ($orderId, $type = 0){
        $conds = array(
            'order_id' => $orderId,
        );
        if ($type > 0) {
            $conds["type"] = $type;
        }
        return $this->daoOrder->getRecordByConds($conds, $this->daoOrder->arrFieldsMap);
    }

    // 根据ids获取订单
    public function getOrderByIds ($orderIds, $type = 0){
        $conds = array(
            sprintf('order_id in (%s)',implode(",", $orderIds)),
        );
        if ($type > 0) {
            $conds["type"] = $type;
        }
        return $this->daoOrder->getListByConds($conds, $this->daoOrder->arrFieldsMap);
    }

    // 获取课程订单
    public function getNmorderById ($orderId){
        return $this->getOrderById($orderId, self::ORDER_TYPE_NORMAL);
    }

    // 获取课程订单
    public function getNmorderByIds ($orderIds){
        return $this->getOrderByIds($orderIds, self::ORDER_TYPE_NORMAL);
    }

    // 根据id获取计划订单
    public function getAporderById ($orderId){
        return $this->getOrderById($orderId, self::ORDER_TYPE_ABROADPLAN);
    }

    // 根据ids获取计划订单
    public function getAporderByIds ($orderIds){
        return $this->getOrderByIds($orderIds, self::ORDER_TYPE_ABROADPLAN);
    }    

    // 根据ids获取计划订单
    public function getAporderByPackageId ($apackageId){
        $conds = array(
            "apackage_id" => $apackageId,
            "type" => self::ORDER_TYPE_ABROADPLAN,
        );
        return $this->daoOrder->getListByConds($conds, $this->daoOrder->arrFieldsMap);
    }

    // 根据ids获取计划订单
    public function getAporderByPackageIds ($apackageIds, $arrFields = array()){
        $conds = array(
            sprintf('apackage_id in (%s)',implode(",", $apackageIds)),
            "type" => self::ORDER_TYPE_ABROADPLAN,
        );
        $arrFields = empty($arrFields) ? $this->daoOrder->arrFieldsMap : $arrFields;
        return $this->daoOrder->getListByConds($conds, $arrFields);
    }

    // 获取列表
    public function getListByConds($conds, $field = array(), $indexs = null, $appends = null) {
        $field = empty($field) || !is_array($field) ? $this->daoOrder->arrFieldsMap : $field;
        return $this->daoOrder->getListByConds($conds, $field, $indexs, $appends);
    }

    // 获取单条
    public function getRecordByConds($conds, $field = array(), $indexs = null, $appends = null) {
        $field = empty($field) || !is_array($field) ? $this->daoOrder->arrFieldsMap : $field;
        return $this->daoOrder->getRecordByConds($conds, $field, $indexs, $appends);
    }
    
    public function getTotalByConds($conds) {
        return  $this->daoOrder->getCntByConds($conds);
    }

    // 根据uids获取订单量
    public function getNmorderTotalBySuids($suids) {
        // 预填充
        $result = array();
        foreach ($suids as $uid) {
            if (!isset($result[$uid])) {
                $result[$uid] = array(
                    "count" => 0,
                    "balance" => 0,
                );
            }
        }
        $conds = array(
            sprintf("student_uid in (%s)", implode(",", $suids)),
            "type" => self::ORDER_TYPE_NORMAL,
        );
        $field = array(
            "count(order_id) as order_count", 
            "sum(balance) as balance", 
            "student_uid"
        );
        $append = array("group by student_uid");
        $lists = $this->daoOrder->getListByConds($conds, $field, null, $append);
        if (empty($lists)) {
            return $result;
        }
        foreach ($lists as $v) {
            $result[$v["student_uid"]]["count"] += intval($v["order_count"]);
            $result[$v["student_uid"]]["balance"] += intval($v["balance"]);
        }
        return $result;
    }

    // get order duration by apckage ids
    public function getAporderDurationByApackageIds ($apackageIds) {
        // 预填充
        $result = array();
        foreach ($apackageIds as $id) {
            if (!isset($result[$id])) {
                $result[$id] = 0;
            }
        }
        $conds = array(
            "type" => Service_Data_Order::ORDER_TYPE_ABROADPLAN,
            sprintf("apackage_id in (%s)", implode(",", $apackageIds))
        );
        $orderDatas = $this->getListByConds($conds, array("order_id", "apackage_id", 'ext'));
        if (empty($orderDatas)) {
            return $result;
        }
        foreach ($orderDatas as $order) {
            if (!isset($result[$order['apackage_id']])) {
                $result[$order['apackage_id']] = 0;
            }

            $ext = json_decode($order["ext"], true);
            if (!empty($ext["schedule_nums"])) {
                $result[$order['apackage_id']] += $ext['schedule_nums'];
            }
        }
        return $result;
    }

    // create noraml order
    public function createNmorder ($profile) {
        $daoChange = new Dao_Orderchange();
        $daoUser = new Dao_User();

        $orderInfo = $profile['order_info'];
        unset($profile['order_info']);

        $this->daoOrder->startTransaction();
        $ret = $this->daoOrder->insertRecords($profile);
        if ($ret == false) {
            $this->daoOrder->rollback();
            return false;
        }

        $orderId = $this->daoOrder->getInsertId();
        if (intval($orderId) <= 0) {
            $this->daoOrder->rollback();
            return false;
        }

        if ($profile['isfree'] == 0) {
            $p2 = array(
                sprintf("balance=balance-%d", $profile['balance']),
            );
            $conds = array(
                "uid" => $profile['student_uid'],
            );
            $ret = $daoUser->updateByConds($conds, $p2);
            if ($ret == false) {
                $this->daoOrder->rollback();
                return false;
            }
        }

        $ext = json_decode($profile['ext'], true);
        $changePorfile = array(
            "order_id"          => $orderId, 
            "student_uid"       => intval($profile['student_uid']), 
            "type"              => Service_Data_Orderchange::CHANGE_CREATE,
            "balance"           => intval($profile['balance']),
            "duration"          => $ext['schedule_nums'],
            "order_info"        => $orderInfo,
            "operator"          => OPERATOR, 
            'update_time'       => time(),
            'create_time'       => time(),
            'ext'               => json_encode(array('isfree' => $profile['isfree'])),
        );

        $ret = $daoChange->insertRecords($changePorfile);
        if ($ret == false) {
            $this->daoOrder->rollback();
            return false;
        }
        $this->daoOrder->commit();
        return true;
    }

    // 删除
    public function deleteNmorder ($orderId, $order, $orderInfo) {
        $this->daoOrder->startTransaction();
        $daoChange = new Dao_Orderchange();
        $changePorfile = array(
            "order_id"          => $orderId, 
            "student_uid"       => intval($order['student_uid']), 
            "type"              => Service_Data_Orderchange::CHANGE_DELETE,
            "balance"           => 0,
            "duration"          => 0,
            "order_info"        => $orderInfo,
            "operator"          => OPERATOR, 
            'update_time'       => time(),
            'create_time'       => time(),
            'ext'               => json_encode(array('isfree' => $order['isfree'])),
        );

        $ret = $daoChange->insertRecords($changePorfile);
        if ($ret == false) {
            $this->daoOrder->rollback();
            return false;
        }
        $ret = $this->daoOrder->deleteByConds(array('order_id' => $orderId));
        if ($ret == false) {
            $this->daoOrder->rollback();
            return false;
        }
        $this->daoOrder->commit();
        return true;
    }

    // 普通订单结转回账户
    public function refundNmorder ($profile, $orderInfo) {
        $this->daoOrder->startTransaction();
        $daoChange = new Dao_Orderchange();

        $ret = $daoChange->insertRecords($profile);
        if ($ret == false) {
            $this->daoOrder->rollback();
            return false;
        }

        // 退款金额更新
        $extra = json_decode($orderInfo['ext'], true);
        if (!isset($extra['change_balance'])) {
            $daoChange->rollback();
            return false;
        }
        $extra['change_balance'] = intval($extra['change_balance']) + intval($profile['balance']);

        $orderData = array(
            sprintf("balance=balance-%d", $profile['balance']),
            'ext' => json_encode($extra),
            'update_time' => time(),
        );
        $conds = array(
            "order_id" => intval($profile['order_id']),
        );
        $ret = $this->daoOrder->updateByConds($conds, $orderData);
        if ($ret == false) {
            $this->daoOrder->rollback();
            return false;
        } 
        // 用户账户填充
        $p2 = array(
            sprintf("balance=balance+%d", $profile['balance']),
        );
        $conds = array(
            "uid" => $profile['student_uid'],
        );
        $daoUser = new Dao_User();
        $ret = $daoUser->updateByConds($conds, $p2);
        if ($ret == false) {
            $this->daoOrder->rollback();
            return false;
        }
        $this->daoOrder->commit();
        return true;
    }

    // 计划服务创建订单
    public function createAporder ($profile) {
        $this->daoOrder->startTransaction();
        $orderInfo = $profile['order_info'];
        unset($profile['order_info']);

        $ret = $this->daoOrder->insertRecords($profile);
        if ($ret == false) {
            $this->daoOrder->rollback();
            return false;
        }

        $orderId = $this->daoOrder->getInsertId();
        if (intval($orderId) <= 0) {
            $this->daoOrder->rollback();
            return false;
        }

        $daoChange = new Dao_Orderchange();
        $ext = json_decode($profile['ext'], true);
        $changePorfile = array(
            "order_id"          => $orderId, 
            "student_uid"       => intval($profile['student_uid']), 
            "type"              => Service_Data_Orderchange::CHANGE_APORDER_ORDER_CHANGE,
            "balance"           => intval($profile['balance']),
            "duration"          => $ext['schedule_nums'],
            "order_info"        => $orderInfo,
            "operator"          => OPERATOR, 
            'update_time'       => time(),
            'create_time'       => time(),
        );

        $ret = $daoChange->insertRecords($changePorfile);
        if ($ret == false) {
            $this->daoOrder->rollback();
            return false;
        }
        $this->daoOrder->commit();
        return true;        
    }

    // 删除留学计划订单
    public function deleteAporder ($orderId, $order, $abroadplanInfo) {
        $this->daoOrder->startTransaction();
        $daoChange = new Dao_Orderchange();
        $changePorfile = array(
            "order_id"          => $orderId, 
            "student_uid"       => intval($order['student_uid']), 
            "type"              => Service_Data_Orderchange::CHANGE_APORDER_ORDER_CHANGE,
            "balance"           => 0,
            "duration"          => 0,
            "order_info"        => json_encode(array(
                "abroadplan_name" => $abroadplanInfo["name"],
                "action_type" => "delete",
            )),
            "operator"          => OPERATOR, 
            'update_time'       => time(),
            'create_time'       => time(),
        );

        $ret = $daoChange->insertRecords($changePorfile);
        if ($ret == false) {
            $this->daoOrder->rollback();
            return false;
        }
        $ret = $this->daoOrder->deleteByConds(array('order_id' => $orderId));
        if ($ret == false) {
            $this->daoOrder->rollback();
            return false;
        }
        $this->daoOrder->commit();
        return true;
    }    

    // 更新留学计划订单课时
    public function updateAporder ($orderId, $profile) {
        $this->daoOrder->startTransaction();
        $orderData = $profile["order"];
        $abroadplan = $profile["abroadplan"];

        $daoChange = new Dao_Orderchange();
        $changePorfile = array(
            "order_id"          => $orderId, 
            "student_uid"       => intval($orderData['student_uid']), 
            "type"              => Service_Data_Orderchange::CHANGE_APORDER_ORDER_CHANGE,
            "balance"           => 0,
            "duration"          => 0,
            "order_info"        => json_encode(array(
                "abroadplan_name"   => $abroadplan["name"],
                "schedule_nums"     => $profile["schedule_nums"],
                "action_type"       => "update",
            )),
            "operator"          => OPERATOR, 
            'update_time'       => time(),
            'create_time'       => time(),
        );

        $ret = $daoChange->insertRecords($changePorfile);
        if ($ret == false) {
            $this->daoOrder->rollback();
            return false;
        }

        $orderProfile = array(
            "ext" => json_encode($orderData['ext']),
        );
        $ret = $this->daoOrder->updateByConds(array('order_id' => $orderId), $orderProfile);
        if ($ret == false) {
            $this->daoOrder->rollback();
            return false;
        }
        $this->daoOrder->commit();
        return true;
    }

}