<?php

class Service_Data_Question {

    private $daoQuestion ;

    public function __construct() {
        $this->daoQuestion = new Dao_Question () ;
    }

    public function getQuestionById ($qid) {
        $arrConds = array(
            'qid'  => $qid,
        );

        $question = $this->daoQuestion->getRecordByConds($arrConds, $this->daoQuestion->arrFieldsMap);
        if (empty($question)) {
            return array();
        }
        return $question;
    }

    public function getQuestionByIds ($qids) {
        $arrConds = array(
            sprintf("qid in (%s)", implode(",", $qids))
        );

        $question = $this->daoQuestion->getRecordByConds($arrConds, $this->daoQuestion->arrFieldsMap);
        if (empty($question)) {
            return array();
        }
        return $question;
    }

    // 创建
    public function create ($profile) {
        return $this->daoQuestion->insertRecords($profile);
    }

    // 列表
    public function getListByConds($conds, $field = array(), $indexs = null, $appends = null) {
        $field = empty($field) || !is_array($field) ? $this->daoQuestion->arrFieldsMap : $field;
        $lists = $this->daoQuestion->getListByConds($conds, $field, $indexs, $appends);
        if (empty($lists)) {
            return array();
        }
        return $lists;
    }

    // 单独一项
    public function getRecordByConds($conds, $field = array(), $indexs = null, $appends = null) {
        $field = empty($field) || !is_array($field) ? $this->daoQuestion->arrFieldsMap : $field;
        $Record = $this->daoQuestion->getRecordByConds($conds, $field, $indexs, $appends);
        if (empty($Record)) {
            return array();
        }
        return $Record;
    }

    public function getTotalByConds($conds) {
        return  $this->daoQuestion->getCntByConds($conds);
    }
}