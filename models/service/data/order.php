<?php

class Service_Data_Order {

    private $daoOrder ;

    const WARNING_BALANCE = 500000; // 分为单位

    public function __construct() {
        $this->daoOrder = new Dao_Order () ;
    }

    // 根据id获取订单
    public function getOrderById ($orderId){
        return $this->daoOrder->getRecordByConds(array('order_id' => $orderId), $this->daoOrder->arrFieldsMap);
    }

    // 根据ids获取订单
    public function getOrderByIds ($orderIds){
        return $this->daoOrder->getListByConds(array(sprintf('order_id in (%s)',implode(",", $orderIds))), $this->daoOrder->arrFieldsMap);
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
    public function getOrderCountByStudentUids($studentUids) {
        $conds = array(
            sprintf("student_uid in (%s)", implode(",", $studentUids))
        );
        $field = array(
            "count(order_id) as order_count", 
            "sum(balance) as balance", 
            "student_uid"
        );
        $append = array("group by student_uid");
        $lists = $this->daoOrder->getListByConds($conds, $field, null, $append);
        if (empty($lists)) {
            return array();
        }
        return $lists;
    }

    // 创建订单
    public function create ($profile) {
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
    public function delete ($orderId, $order, $orderInfo) {
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


    public function getDataList ($params) {
        $sql = "select 
                u.uid, u.nickname, u.bpid, o.*
            from 
                tblUser u left join tblOrder o
            on 
                u.uid = o.student_uid
            where u.type=12 ";

        $where = "";
        if (!empty($params['nickname'])) {
            $where .= " and u.nickname like '%" . $params['nickname'] . "%' ";
        }
        if ($params['bpid'] > 0) {
            $where .= " and u.bpid = " .$params['bpid']. " ";
        }
        if (!empty($params['start_time'])) {
            $where .= " and o.update_time >= " .$params['start_time']. " ";
            $where .= " and o.update_time <= " .$params['end_time']. " ";
        }
        if (!empty($where)) {
            $sql .= $where;
        }

        if (empty($params['is_export'])) {
            $sql .= " limit " . $params['pn'] . ", " . $params['rn'];
        }

        $lists = $this->daoOrder->query($sql);
        if (empty($lists)) {
            return array();
        }
        
        return $lists;
    }

    public function getDataTotal ($params) {
        $sql = "select 
                count(*) as count
            from 
                tblUser u left join tblOrder o
            on 
                u.uid = o.student_uid
            where u.type=12 ";

        $where = "";
        if (!empty($params['nickname'])) {
            $where .= " and u.nickname like '%" . $params['nickname'] . "%' ";
        }
        if ($params['bpid'] > 0) {
            $where .= " and u.bpid = " .$params['bpid']. " ";
        }
        if (!empty($params['start_time'])) {
            $where .= " and o.update_time >= " .$params['start_time']. " ";
            $where .= " and o.update_time <= " .$params['end_time']. " ";
        }
        if (!empty($where)) {
            $sql .= $where;
        }

        if (empty($params['is_export'])) {
            $sql .= " limit " . $params['pn'] . ", " . $params['rn'];
        }

        $data = $this->daoOrder->query($sql);
        return  empty($data[0]['count']) ? 0 : intval($data[0]['count']);
    }

}