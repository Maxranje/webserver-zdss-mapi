<?php

class Service_Data_Orderchange {

    private $daoOrderchange ;

    const CHANGE_CREATE = 1;
    const CHANGE_REFUND = 2;
    const CHANGE_DELETE = 3;
    const CHANGE_APORDER_PACKAGE_CREATE         = 4;    // 服务下单
    const CHANGE_APORDER_PACKAGE_DELETE         = 5;    // 服务删除
    const CHANGE_APORDER_PACKAGE_OVER           = 6;    // 服务完结
    const CHANGE_APORDER_ORDER_CHANGE           = 7;    // 订单变更
    const CHANGE_APORDER_DURATION_ADD           = 8;   //  添加服务课时
    const CHANGE_APORDER_PACKAGE_TRANS          = 9;   //  服务结转

    public static $changeNormalMap = [
        self::CHANGE_CREATE,
        self::CHANGE_REFUND,
        self::CHANGE_DELETE,
    ];

    public static $changeAporderMap = [
        self::CHANGE_APORDER_PACKAGE_CREATE,
        self::CHANGE_APORDER_PACKAGE_DELETE,
        self::CHANGE_APORDER_DURATION_ADD,
        self::CHANGE_APORDER_PACKAGE_OVER,
        self::CHANGE_APORDER_ORDER_CHANGE,
        self::CHANGE_APORDER_PACKAGE_TRANS,
    ];

    public function __construct() {
        $this->daoOrderchange = new Dao_Orderchange () ;
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