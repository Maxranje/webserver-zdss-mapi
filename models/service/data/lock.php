<?php

class Service_Data_Lock {

    private $daoLock;

    public function __construct() {
        $this->daoLock = new Dao_Lock () ;
    }

    // 根据UID获取列表时间
    public function getListByUid ($uid, $sts, $ets) {
        $conds = array(
            'uid'   => $uid,
            sprintf("start_time >= %d", $sts),
            sprintf("end_time <= %d", $ets),
        );
        $lists = $this->daoLock->getListByConds($conds, $this->daoLock->arrFieldsMap);
        if (empty($lists)) {
            return array();
        }
        return $lists;
    }


    // 创建
    public function create ($params) {
        $this->daoLock->startTransaction();
        foreach ($params['need_times'] as $time) {
            $profile = array(
                "uid"           => $params['teacher_uid'],
                "type"          => Service_Data_Profile::USER_TYPE_TEACHER,
                "start_time"    => $time['sts'] , 
                "end_time"      => $time['ets'], 
                "operator"      => OPERATOR,
                "update_time"   => time(),
                "create_time"   => time(), 
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

    // 删除
    public function delete ($id) {
        return $this->daoLock->deleteByConds(array('id' => $id));
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

}