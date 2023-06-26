<?php

class Service_Data_Subject {

    private $daoSubject ;

    const PIDS = [];
    const PPIDS = [];

    public function __construct() {
        $this->daoSubject = new Dao_Subject () ;
    }

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

    public function getSubjectByName ($name){
        $arrConds = array(
            'name'  => $name,
        );

        $subject = $this->daoSubject->getRecordByConds($arrConds, $this->daoSubject->arrFieldsMap);
        if (empty($subject)) {
            return array();
        }

        return $subject;
    }


    public function editSubject ($id, $profile) {
        $arrConds = array(
            'id'  => $id,
        );

        $ret = $this->daoSubject->updateByConds($arrConds, $profile);
        return $ret;
    }

    public function createSubject ($profile) {
        $ret = $this->daoSubject->insertRecords($profile);
        if ($ret == false) {
            return false;
        }

        $id = $this->daoSubject->getInsertId();

        $profile['id'] = $id;
        return $profile;
    }

    public function deleteSubject ($id) {
        $this->daoSubject->startTransaction();
        $daoColumn = new Dao_Column();
        $daoSchedule = new Dao_Schedule();

        $conds = array(
            'subject_id' => $id,
        );
        $columnInfo = $daoColumn->getListByConds($conds, array("id"));
        $columnInfo = array_column($columnInfo, "id");
        if (!empty($columnInfo)) {
            $conds = array(
                sprintf("column_id in (%s)", implode(",", $columnInfo)),
                "state in (1,2)",
            );
            $ret = $daoSchedule->deleteByConds($conds);
            if ($ret === false) {
                $this->daoSubject->rollback();
                return false;
            }
        }

        $conds = array(
            "subject_id" => $id,
        );
        $ret = $daoColumn->deleteByConds($conds);
        if ($ret === false) {
            $this->daoSubject->rollback();
            return false;
        }

        $conds = array(
            'id' => $id,
        );
        $ret = $this->daoSubject->deleteByConds($conds);
        if ($ret === false) {
            $this->daoSubject->rollback();
            return false;
        }
        $this->daoSubject->commit();
        return $ret;
    }

    public function getListByConds($conds, $field = array(), $indexs = null, $appends = null) {
        $field = empty($field) || !is_array($field) ? $this->daoSubject->arrFieldsMap : $field;
        $lists = $this->daoSubject->getListByConds($conds, $field, $indexs, $appends);
        if (empty($lists)) {
            return array();
        }
        foreach ($lists as $index => $item) {
            $item['create_time']  = date('Y年m月d日', $item['create_time']);
            $item['update_time']  = date('Y年m月d日', $item['update_time']);
            $lists[$index] = $item;
        }
        return $lists;
    }

    public function getTotalByConds($conds) {
        return  $this->daoSubject->getCntByConds($conds);
    }

}