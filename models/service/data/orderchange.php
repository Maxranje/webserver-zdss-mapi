<?php

class Service_Data_Orderchange {

    private $daoOrderchange ;

    const CHANGE_CREATE = 1;
    const CHANGE_REFUND = 2;
    const CHANGE_DELETE = 3;

    public function __construct() {
        $this->daoOrderchange = new Dao_Orderchange () ;
    }

    // 创建
    public function create ($profile, $orderInfo) {
        $this->daoOrderchange->startTransaction();

        $ret = $this->daoOrderchange->insertRecords($profile);
        if ($ret == false) {
            $this->daoOrderchange->rollback();
            return false;
        }

        // 退款金额更新
        $extra = json_decode($orderInfo['ext'], true);
        if (!isset($extra['change_balance'])) {
            $this->daoOrderchange->rollback();
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
        $daoOrder = new Dao_Order();
        $ret = $daoOrder->updateByConds($conds, $orderData);
        if ($ret == false) {
            $this->daoOrderchange->rollback();
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
            $this->daoOrderchange->rollback();
            return false;
        }
        $this->daoOrderchange->commit();
        return true;
    }

    // 获取列表
    public function getListByConds($conds, $field = array(), $indexs = null, $appends = null) {
        $field = empty($field) || !is_array($field) ? $this->daoOrderchange->arrFieldsMap : $field;
        return $this->daoOrderchange->getListByConds($conds, $field, $indexs, $appends);
    }

    // 获取单条
    public function getRecordByConds($conds, $field = array(), $indexs = null, $appends = null) {
        $field = empty($field) || !is_array($field) ? $this->daoOrderchange->arrFieldsMap : $field;
        return $this->daoOrderchange->getRecordByConds($conds, $field, $indexs, $appends);
    }

    public function getTotalByConds($conds) {
        return  $this->daoOrderchange->getCntByConds($conds);
    }

}