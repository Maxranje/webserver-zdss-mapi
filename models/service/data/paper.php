<?php

class Service_Data_Paper {

    private $daoPaper ;

    public function __construct() {
        $this->daoPaper = new Dao_Paper () ;
    }

    public function getPaperById ($qid) {
        $arrConds = array(
            'qid'  => $qid,
        );

        $Paper = $this->daoPaper->getRecordByConds($arrConds, $this->daoPaper->arrFieldsMap);
        if (empty($Paper)) {
            return array();
        }
        return $Paper;
    }

    public function getPaperByIds ($qids) {
        $arrConds = array(
            sprintf("qid in (%s)", implode(",", $qids))
        );

        $Paper = $this->daoPaper->getRecordByConds($arrConds, $this->daoPaper->arrFieldsMap);
        if (empty($Paper)) {
            return array();
        }
        return $Paper;
    }

    // 创建
    public function create ($profile) {
        $this->daoPaper->startTransaction();
        
        return $this->daoPaper->insertRecords($profile);
    }

    // 列表
    public function getListByConds($conds, $field = array(), $indexs = null, $appends = null) {
        $field = empty($field) || !is_array($field) ? $this->daoPaper->arrFieldsMap : $field;
        $lists = $this->daoPaper->getListByConds($conds, $field, $indexs, $appends);
        if (empty($lists)) {
            return array();
        }
        return $lists;
    }

    // 单独一项
    public function getRecordByConds($conds, $field = array(), $indexs = null, $appends = null) {
        $field = empty($field) || !is_array($field) ? $this->daoPaper->arrFieldsMap : $field;
        $Record = $this->daoPaper->getRecordByConds($conds, $field, $indexs, $appends);
        if (empty($Record)) {
            return array();
        }
        return $Record;
    }

    public function getTotalByConds($conds) {
        return  $this->daoPaper->getCntByConds($conds);
    }
}