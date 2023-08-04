<?php

class Service_Data_Subject {

    private $daoSubject ;

    public function __construct() {
        $this->daoSubject = new Dao_Subject () ;
    }

    // 通过ID获取科目
    public function getSubjectById ($id){
        $arrConds = array(
            'id'  => $id,
        );

        $subject = $this->daoSubject->getRecordByConds($arrConds, $this->daoSubject->arrFieldsMap);
        if (empty($subject)) {
            return array();
        }

        return $subject;
    }

    // 对科目编辑
    public function editSubject ($id, $profile) {
        return $this->daoSubject->updateByConds(array('id'  => $id), $profile);
    }

    // 创建科目
    public function createSubject ($profile) {
        return $this->daoSubject->insertRecords($profile);
    }

    // 删除科目
    public function deleteSubject ($id) {
        return $this->daoSubject->deleteByConds(array('id'  => $id));
    }

    // 获取列表
    public function getListByConds($conds, $field = array(), $indexs = null, $appends = null) {
        $field = empty($field) || !is_array($field) ? $this->daoSubject->arrFieldsMap : $field;
        $lists = $this->daoSubject->getListByConds($conds, $field, $indexs, $appends);
        if (empty($lists)) {
            return array();
        }
        foreach ($lists as $index => $item) {
            $item['create_time']  = date('Y年m月d日', $item['create_time']);
            $item['update_time']  = date('Y年m月d日', $item['update_time']);
            $item['price_info']   = sprintf("%.2f", $item['price'] / 100);
            $lists[$index] = $item;
        }
        return $lists;
    }

    // 获取单条
    public function getRecordByConds($conds, $field = array(), $indexs = null, $appends = null) {
        $field = empty($field) || !is_array($field) ? $this->daoSubject->arrFieldsMap : $field;
        $record = $this->daoSubject->getRecordByConds($conds, $field, $indexs, $appends);
        if (empty($record)) {
            return array();
        }
        $record['create_time']  = date('Y年m月d日', $record['create_time']);
        $record['update_time']  = date('Y年m月d日', $record['update_time']);
        return $record;
    }

    public function getTotalByConds($conds) {
        return  $this->daoSubject->getCntByConds($conds);
    }

}