<?php

class Service_Data_Operationlog {
    private $daoOperationlog;

    const SCHEDULE_EDIT = 42;

    public function __construct() {
        $this->daoOperationlog = new Dao_Operationlog () ;
    }

    public function writeLog($point, $workId, $from, $to) {
        $data = array(
            "uid" => OPERATOR,
            "point" => $point,
            "work_id" => $workId,
            "original_data" => $from,
            "current_data" => $to,
            "update_time" => time(),
            "create_time" => time(),
        );
        return $this->daoOperationlog->insertRecords($data);
    }

    // 获取列表
    public function getListByConds($conds, $field = array(), $indexs = null, $appends = null) {
        $field = empty($field) || !is_array($field) ? $this->daoOperationlog->arrFieldsMap : $field;
        $lists = $this->daoOperationlog->getListByConds($conds, $field, $indexs, $appends);
        if (empty($lists)) {
            return array();
        }
        return $lists;
    }

    // 获取数量
    public function getTotalByConds($conds) {
        return  $this->daoOperationlog->getCntByConds($conds);
    }
}