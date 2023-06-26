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
        $arrFields = $this->daoColumn->arrFieldsMap;

        $Column = $this->daoColumn->getRecordByConds($arrConds, $arrFields);
        if (empty($Column)) {
            return array();
        }

        return $Column;
    }

    public function getColumnByTSId ($teacherId, $subjectId){
        $arrConds = array(
            'teacher_id'  => $teacherId,
            'subject_id'  => $subjectId,
        );
        $arrFields = $this->daoColumn->arrFieldsMap;

        $Column = $this->daoColumn->getRecordByConds($arrConds, $arrFields);
        if (empty($Column)) {
            return array();
        }

        return $Column;
    }

    public function getColumnByTId ($teacherId){
        $arrConds = array(
            'teacher_id'  => $teacherId,
        );
        $arrFields = $this->daoColumn->arrFieldsMap;

        $Column = $this->daoColumn->getListByConds($arrConds, $arrFields);
        if (empty($Column)) {
            return array();
        }

        return $Column;
    }

    public function editColumn ($conds, $profile) {
        return $this->daoColumn->updateByConds($conds, $profile);
    }

    public function createColumn ($profile) {
        $ret = $this->daoColumn->insertRecords($profile);
        if ($ret == false) {
            return false;
        }

        $id = $this->daoColumn->getInsertId();

        $profile['id'] = $id;
        return $profile;
    }

    public function deleteColumn ($teacherId, $subjectId) {
        $columnInfo = $this->getColumnByTSId($teacherId, $subjectId);
        if (empty($columnInfo)) {
            return false;
        }

        $this->daoColumn->startTransaction();
        $daoSchedule = new Dao_Schedule();

        $conds = array(
            'column_id' => $columnInfo['id'],
            "state = 1",
        );
        $ret = $daoSchedule->deleteByConds($conds);
        if ($ret === false) {
            $this->daoColumn->rollback();
            return false;
        }

        $conds = array(
            "id" => $columnInfo['id'],
        );
        $ret = $this->daoColumn->deleteByConds($conds);
        if ($ret === false) {
            $this->daoColumn->rollback();
            return false;
        }
        $this->daoColumn->commit();
        return $ret;
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