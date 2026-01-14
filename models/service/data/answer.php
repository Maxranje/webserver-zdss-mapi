<?php

class Service_Data_Answer {

    private $daoAnswer;

    public function __construct() {
        $this->daoAnswer = new Dao_Answer();
    }

    public function getAnswerById ($qid) {
        $arrConds = array(
            'qid'  => $qid,
        );

        $Answer = $this->daoAnswer->getRecordByConds($arrConds, $this->daoAnswer->arrFieldsMap);
        if (empty($Answer)) {
            return array();
        }
        return $Answer;
    }

    public function getAnswerByIds ($qids, $field = array()) {
        $arrConds = array(
            sprintf("qid in (%s)", implode(",", $qids))
        );

        $field = empty($field) ? $this->daoAnswer->arrFieldsMap : $field;
        $Answers = $this->daoAnswer->getListByConds($arrConds, $field);
        if (empty($Answers)) {
            return array();
        }
        return $Answers;
    }

    public function getAnswerByQid ($qid) {
        $arrConds = array(
            "qid" => $qid,
        );

        $answers = $this->daoAnswer->getListByConds($arrConds, $this->daoAnswer->arrFieldsMap);
        if (empty($answers)) {
            return array();
        }
        return $answers;
    }    

    public function getAnswerByQids ($qids) {
        $arrConds = array(
            sprintf("qid in (%s)", implode(",", $qids))
        );

        $answers = $this->daoAnswer->getListByConds($arrConds, $this->daoAnswer->arrFieldsMap);
        if (empty($answers)) {
            return array();
        }

        $ret = array();
        foreach ($answers as $item) {
            if (!isset($ret[$item["qid"]])) {
                $ret[$item["qid"]] = array();
            }
            $ret[$item["qid"]][] = $item;
        }
        return $ret;
    }       
    
    // 列表
    public function getListByConds($conds, $field = array(), $indexs = null, $appends = null) {
        $field = empty($field) || !is_array($field) ? $this->daoAnswer->arrFieldsMap : $field;
        $lists = $this->daoAnswer->getListByConds($conds, $field, $indexs, $appends);
        if (empty($lists)) {
            return array();
        }
        return $lists;
    }

    // 单独一项
    public function getRecordByConds($conds, $field = array(), $indexs = null, $appends = null) {
        $field = empty($field) || !is_array($field) ? $this->daoAnswer->arrFieldsMap : $field;
        $Record = $this->daoAnswer->getRecordByConds($conds, $field, $indexs, $appends);
        if (empty($Record)) {
            return array();
        }
        return $Record;
    }

    public function getTotalByConds($conds) {
        return  $this->daoAnswer->getCntByConds($conds);
    }

}