<?php

class Service_Data_Records {

    const RECORDS_NOMARL = 1;
    const RECORDS_REVOKE = 2;

    private $daoRecords;

    public function __construct() {
        $this->daoRecords = new Dao_Records () ;
    }

    // 获取order 所有消耗的
    public function getOrderPayMoney ($orderIds) {
        $conds = array(
            sprintf("order_id in (%s)", implode(",", $orderIds)),
        );
        $field = array(
            "sum(money) as money",
            "order_id",
        );
        $appends = array(
            "group by order_id"
        );
        $lists = $this->daoRecords->getListByConds($conds, $field, null, $appends);
        if (empty($lists)) {
            return array();
        }
        return $lists;
    }

    // 获取列表
    public function getListByConds($conds, $field = array(), $indexs = null, $appends = null) {
        $field = empty($field) || !is_array($field) ? $this->daoRecords->arrFieldsMap : $field;
        $lists = $this->daoRecords->getListByConds($conds, $field, $indexs, $appends);
        if (empty($lists)) {
            return array();
        }
        return $lists;
    }

    // 获取数量
    public function getTotalByConds($conds) {
        return  $this->daoRecords->getCntByConds($conds);
    }

    // 根据bpid获取列表数据
    public function getListByBpid($uid, $bpid, $scheduleId, $category, $dataRange, $pn =0 , $rn = 20) {

        $sql = sprintf("select a.* from tblRecords a left join tblUser b on a.uid = b.uid where b.bpid= %d ", $bpid);
        if (!empty($uid)) {
            $sql .= sprintf(" and a.uid in (%s)", implode(",", $uid));
        }
        if ($scheduleId > 0) {
            $sql .= " and a.schedule_id = " . $scheduleId;
        }
        if ($category > 0) {
            $sql .= " and a.category = " . $category;
        }
        if (!empty($dataRange)) {
            $sql .= sprintf(" a.create_time >= %d", $dataRange[0]);
            $sql .= sprintf(" a.create_time <= %d", ($dataRange[1] + 1));
        }
        $sql .= " limit {$pn} , {$rn}";

        $data = $this->daoRecords->query($sql);
        return empty($data) || !is_array($data) ? array() : $data;
    }

    // 根据bpid获取列表数据
    public function getTotalByBpid($uid, $bpid, $scheduleId, $category, $dataRange) {

        $sql = sprintf("select count(a.*) as count from tblRecords a left join tblUser b on a.uid = b.uid where b.bpid= %d ", $bpid);
        if (!empty($uid)) {
            $sql .= sprintf(" and a.uid in (%s)", implode(",", $uid));
        }
        if ($uid > 0) {
            $sql .= " and a.schedule_id = " . $scheduleId;
        }
        if ($category > 0) {
            $sql .= " and a.category = " . $category;
        }
        if (!empty($dataRange)) {
            $sql .= sprintf(" a.create_time >= %d", $dataRange[0]);
            $sql .= sprintf(" a.create_time <= %d", ($dataRange[1] + 1));
        }

        $data = $this->daoRecords->query($sql);
        $total = 0;
        if (!empty($data[0]['count'])) {
            $total = intval($data[0]['total']);
        }
        return $total;
    }


    // 根据uid和时间获取账户记录
    public function getCapitalListsByUids($uids, $sts, $ets){
        $daoCapital = new Dao_Capital();

        $sql = sprintf("select * from tblCapital where update_time >= %d and update_time <= %d and state = 1 and uid in (%s)", $sts, $ets, implode(",", $uids));

        $lists = $daoCapital->query($sql);
        if (empty($lists)) {
            return array();
        }
        $result = array();
        foreach ($lists as $item) {
            if (!isset($result[$item['uid']])) {
                $result[$item['uid']] = array(
                    "recharge" => 0,
                    "refund" => 0,
                    "refund_back" => 0,
                );
            }
            if ($item['type'] == Service_Data_Profile::RECHARGE) {
                $result[$item['uid']]["recharge"] += $item['capital'];
            } else if ($item['type'] == Service_Data_Profile::REFUND) {
                $ext = empty($item['ext']) ? array() : json_decode($item['ext'], true);
                if (empty($ext['refund_balance']) && empty($ext['refund_back_balance'])) {
                    $result[$item['uid']]["refund"] += $item['capital'];
                } else {
                    $result[$item['uid']]["refund"] += intval($ext['refund_balance']);
                    $result[$item['uid']]["refund_back"] += empty($ext['refund_back_balance']) ? 0 : intval($ext['refund_back_balance']);
                }
            }
        }
        return $result;
    }

    public function getRecordsListsByUids($uids, $sts, $ets){

        $sql = sprintf("select * from tblRecords where state = 1 and type = 12 and category = 1 and update_time >= %d and update_time <= %d and uid in (%s)", $sts, $ets, implode(",", $uids));

        $lists = $this->daoRecords->query($sql);
        if (empty($lists)) {
            return array();
        }
        $result = array();
        foreach ($lists as $item) {
            if (!isset($result[$item['uid']])) {
                $result[$item['uid']] = array(
                    "checkjob" => 0,
                );
            }
            $result[$item['uid']]['checkjob'] += intval($item['money']);
        }
        return $result;
    }

    public function getRecordsListsByOrderIds($orderIds, $sts, $ets){

        $sql = sprintf("select * from tblRecords where state = 1 and type = 12 and category = 1 and update_time >= %d and update_time <= %d and order_id in (%s)", $sts, $ets, implode(",", $orderIds));

        $lists = $this->daoRecords->query($sql);
        if (empty($lists)) {
            return array();
        }
        $result = array();
        foreach ($lists as $item) {
            if (!isset($result[$item['order_id']])) {
                $result[$item['order_id']] = array(
                    "checkjob" => 0,
                );
            }
            $result[$item['order_id']]['checkjob'] += intval($item['money']);
        }
        return $result;
    }


    public function getOrderChangeListsByOrderIds($orderIds, $sts, $ets){
        $daoOrderChange = new Dao_Orderchange();

        $sql = sprintf("select * from tblOrderChange where type = 2 and update_time >= %d and update_time <= %d and order_id in (%s)", $sts, $ets, implode(",", $orderIds));

        $lists = $daoOrderChange->query($sql);
        if (empty($lists)) {
            return array();
        }
        $result = array();
        foreach ($lists as $item) {
            if (!isset($result[$item['order_id']])) {
                $result[$item['order_id']] = array(
                    "change_balance" => 0,
                    "change_duration" => 0,
                );
            }
            $result[$item['order_id']]['change_duration'] += intval($item['duration']);
            $result[$item['order_id']]['change_balance'] += intval($item['balance']);
        }
        return $result;
    }
}