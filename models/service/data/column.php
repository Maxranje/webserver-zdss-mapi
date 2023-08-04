<?php

class Service_Data_Column {

    private $daoColumn ;

    public function __construct() {
        $this->daoColumn = new Dao_Column () ;
    }

    public function getColumnById ($id){
        $arrConds = array(
            'id'  => $id,
        );

        $Column = $this->daoColumn->getRecordByConds($arrConds, $this->daoColumn->arrFieldsMap);
        if (empty($Column)) {
            return array();
        }

        return $Column;
    }

    public function getColumnByTSId ($teacherUid, $subjectId){
        $arrConds = array(
            'teacher_uid'  => $teacherUid,
            'subject_id'  => $subjectId,
        );

        $Column = $this->daoColumn->getRecordByConds($arrConds, $this->daoColumn->arrFieldsMap);
        if (empty($Column)) {
            return array();
        }

        return $Column;
    }

    public function getColumnByTId ($teacherUid){
        $arrConds = array(
            'teacher_uid'  => $teacherUid,
        );
        $Column = $this->daoColumn->getListByConds($arrConds, $this->daoColumn->arrFieldsMap);
        if (empty($Column)) {
            return array();
        }

        return $Column;
    }

    public function getColumnBySId ($subjectId){
        $arrConds = array(
            'subject_id'  => $subjectId,
        );
        $Column = $this->daoColumn->getListByConds($arrConds, $this->daoColumn->arrFieldsMap);
        if (empty($Column)) {
            return array();
        }

        return $Column;
    }

    // 编辑
    public function editColumn ($id, $profile) {
        return $this->daoColumn->updateByConds(array('id' => $id), $profile);
    }

    // 创建
    public function createColumn ($profile) {
        return $this->daoColumn->insertRecords($profile);
    }

    public function deleteColumn ($id) {
        return $this->daoColumn->deleteByConds(array('id' => $id));
    }

    public function getListByConds($conds, $field = array(), $indexs = null, $appends = null) {
        $field = empty($field) || !is_array($field) ? $this->daoColumn->arrFieldsMap : $field;
        $lists = $this->daoColumn->getListByConds($conds, $field, $indexs, $appends);
        if (empty($lists)) {
            return array();
        }
        return $lists;
    }

    public function getTotalByConds($conds) {
        return  $this->daoColumn->getCntByConds($conds);
    }
}