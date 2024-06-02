<?php

class Service_Data_Capital {
    private $daoCapital;

    public function __construct() {
        $this->daoCapital = new Dao_Capital () ;
    }

    // get capital id
    public function getCapitalById ($id) {
        $conds = array(
            'id' => $id,
        );
        $data = $this->daoCapital->getRecordByConds($conds, $this->daoCapital->arrFieldsMap);
        if(empty($data)) {
            return array();
        }
        return $data;
    }

    //
    public function getCapitalByIds ($ids) {
        $arrConds = array(
            sprintf("id in (%s)", implode(",", $ids))
        );

        $data = $this->daoCapital->getListByConds($arrConds, $this->daoCapital->arrFieldsMap);
        if (empty($data)) {
            return array();
        }

        return $data;
    }


    // 获取列表
    public function getListByConds($conds, $field = array(), $indexs = null, $appends = null) {
        $field = empty($field) || !is_array($field) ? $this->daoCapital->arrFieldsMap : $field;
        $lists = $this->daoCapital->getListByConds($conds, $field, $indexs, $appends);
        if (empty($lists)) {
            return array();
        }
        return $lists;
    }

    // 获取数量
    public function getTotalByConds($conds) {
        return  $this->daoCapital->getCntByConds($conds);
    }
}