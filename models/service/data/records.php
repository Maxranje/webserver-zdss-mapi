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
}