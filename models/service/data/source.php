<?php

class Service_Data_Source {

    private $daoSource ;

    public function __construct() {
        $this->daoSource = new Dao_Source () ;
    }

    public function getSourceById ($id) {
        $arrConds = array(
            'id'  => $id,
        );

        $data = $this->daoSource->getRecordByConds($arrConds, $this->daoSource->arrFieldsMap);
        if (empty($data)) {
            return array();
        }

        return $data;
    }

    public function getSourceByName ($name) {
        $arrConds = array(
            'name'  => $name,
        );

        $data = $this->daoSource->getRecordByConds($arrConds, $this->daoSource->arrFieldsMap);
        if (empty($data)) {
            return array();
        }

        return $data;
    }

    public function getSourceByIds ($ids) {
        $arrConds = array(
            sprintf("id in (%s)", implode(",", $ids))
        );

        $data = $this->daoSource->getListByConds($arrConds, $this->daoSource->arrFieldsMap);
        if (empty($data)) {
            return array();
        }

        return $data;
    }

    // 创建
    public function create ($id, $name, $subName) {
        $this->daoSource->startTransaction();
        if ($id <= 0) {
            $profile = array(
                "name" => $name,
                "create_time" => time(),
                "update_time" => time(),
            );
            $ret = $this->daoSource->insertRecords($profile);
            if ($ret == false) {
                $this->daoSource->rollback();
                return false;
            }
            $id = $this->daoSource->getInsertId();
            if ($id <=0 ) {
                $this->daoSource->rollback();
                return false;
            }
        }
        
        $profile = array(
            "name" => $subName,
            "parent_id" => intval($id),
            "create_time" => time(),
            "update_time" => time(),
        );
        $ret = $this->daoSource->insertRecords($profile);
        if ($ret == false) {
            $this->daoSource->rollback();
            return false;
        }   
        $this->daoSource->commit();     
        return true;
    }

    // 修改
    public function update ($id, $profile) {
        return $this->daoSource->updateByConds(array('id'=>$id), $profile);
    }

    // 删除
    public function delete ($id) {
        $this->daoSource->startTransaction();
        
        // 删掉学生关联
        $dao = new Dao_Paper();
        $ret = $dao->updateByConds(
            array("subject_id" => $id), 
            array("subject_id" => 0));
        if ($ret == false) {
            $this->daoSource->rollback();
            return false;
        }

        // 删掉记录
        $ret =  $this->daoSource->deleteByConds(array('id'=>$id));
        if ($ret == false) {
            $this->daoSource->rollback();
            return false;
        }
        $this->daoSource->commit();
        return true;
    }

    // 列表
    public function getListByConds($conds, $field = array(), $indexs = null, $appends = null) {
        $field = empty($field) || !is_array($field) ? $this->daoSource->arrFieldsMap : $field;
        $lists = $this->daoSource->getListByConds($conds, $field, $indexs, $appends);
        if (empty($lists)) {
            return array();
        }
        return $lists;
    }

    // 单独一项
    public function getRecordByConds($conds, $field = array(), $indexs = null, $appends = null) {
        $field = empty($field) || !is_array($field) ? $this->daoSource->arrFieldsMap : $field;
        $Record = $this->daoSource->getRecordByConds($conds, $field, $indexs, $appends);
        if (empty($Record)) {
            return array();
        }
        return $Record;
    }

    public function getTotalByConds($conds) {
        return  $this->daoSource->getCntByConds($conds);
    }
}