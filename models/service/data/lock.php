<?php

class Service_Data_Lock {

    private $daoLock;

    public function __construct() {
        $this->daoLock = new Dao_Lock () ;
    }

    public function getListByConds($conds, $field = array(), $indexs = null, $appends = null) {
        $field = empty($field) || !is_array($field) ? $this->daoLock->arrFieldsMap : $field;
        $lists = $this->daoLock->getListByConds($conds, $field, $indexs, $appends);
        if (empty($lists)) {
            return array();
        }
        return $lists;
    }

    public function getTotalByConds($conds) {
        return  $this->daoLock->getCntByConds($conds);
    }

    public function create ($params) {
        $this->daoLock->startTransaction();
        foreach ($params['needTimes'] as $time) {
            $profile = array(
                "uid"  => $params['teacher_id'],
                "type" => $params['type'],
                "start_time"  => $time['sts'] , 
                "end_time"  => $time['ets'], 
                "operator" => OPERATOR,
                "update_time"  => time(),
                "create_time"  => time(), 
            );
            $ret = $this->daoLock->insertRecords($profile);
            if ($ret == false) {
                $this->daoLock->rollback();
                return false;
            }
        }
        $this->daoLock->commit();
        return true;
    }

    public function delete ($id) {
        $conds = array(
            'id' => $id,
        );
        $ret = $this->daoLock->deleteByConds($conds);
        return $ret;
    }

    public function getLockListByUid ($uid, $sts, $ets) {
        // 锁时间的数据
        $conds = array();
        $conds[] = "uid=".$uid;
        $conds[] = "start_time >= ".$sts;
        $conds[] = "end_time <= ".$ets;
        $locks = $this->getListByConds($conds);
        return empty($locks) ? array() : $locks;
    }
}