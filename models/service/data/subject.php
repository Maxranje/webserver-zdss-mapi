<?php

class Service_Data_Subject {

    private $daoSubject ;

    public function __construct() {
        $this->daoSubject = new Dao_Subject () ;
    }

    // 获取科目信息
    public function getSubjectById ($id) {
        return $this->daoSubject->getRecordByConds(array('id' => $id), $this->daoSubject->arrFieldsMap);
    } 

    public function getSubjectByParentID ($id) {
        return $this->daoSubject->getListByConds(array('parent_id' => $id), $this->daoSubject->arrFieldsMap);
    } 

    public function getSubSubjectByName ($subjectId, $name) {
        return $this->daoSubject->getRecordByConds(array('parent_id' => $subjectId, 'name' => $name), $this->daoSubject->arrFieldsMap);
    } 

    public function getParentSubjectByName ($name) {
        return $this->daoSubject->getRecordByConds(array('parent_id' => 0, 'name' => $name), $this->daoSubject->arrFieldsMap);
    } 

    public function getSubjectByIds ($ids) {
        return $this->daoSubject->getListByConds(array(sprintf("id in (%s)", implode(",", $ids))), $this->daoSubject->arrFieldsMap);
    } 

    public function getListByConds ($conds, $field = array(), $indexs = null, $appends = null) {
        $field = empty($field) || !is_array($field) ? $this->daoSubject->arrFieldsMap : $field;
        return $this->daoSubject->getListByConds($conds, $field, $indexs, $appends);
    }

    public function getSubjectTotalByConds ($conds) {
        return $this->daoSubject->getCntByConds($conds);
    }

    public function createSubject($profile) {
        return $this->daoSubject->insertRecords($profile);
    }

    public function editSubject($id, $profile){
        return $this->daoSubject->updateByConds(array('id' => $id), $profile);
    }

    public function deleteSubject ($id) {
        return $this->daoSubject->deleteByConds(array('id' => $id));
    }

}