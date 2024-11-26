<?php

class Service_Data_Group {

    const GROUP_ABLE    = 1;
    const GROUP_DISABLE = 2;

    private $daoGroup ;
    
    public function __construct() {
        $this->daoGroup = new Dao_Group () ;
    }

    // 根据ID获取班级信息
    public function getGroupById ($id){
        $arrConds = array(
            'id'  => $id,
        );

        $arrFields = $this->daoGroup->arrFieldsMap;

        $Group = $this->daoGroup->getRecordByConds($arrConds, $arrFields);
        if (empty($Group)) {
            return array();
        }

        return $Group;
    }

    // 根据IDs获取班级信息
    public function getGroupByIds ($ids){
        $arrConds = array(
            sprintf("id in (%s)", implode(",", $ids))
        );

        $arrFields = $this->daoGroup->arrFieldsMap;

        $Group = $this->daoGroup->getListByConds($arrConds, $arrFields);
        if (empty($Group)) {
            return array();
        }

        return $Group;
    }

    // 创建
    public function create ($profile) {
        return $this->daoGroup->insertRecords($profile);
    }

    // 修改
    public function update ($id, $profile) {
        if (isset($profile['area_operator'])) {
            $this->daoGroup->startTransaction();

            // 更新班级
            $ret = $this->daoGroup->updateByConds(array('id'=>$id), $profile);
            if ($ret == false) {
                $this->daoGroup->rollback();
                return false;
            }
            // 根据助教更新所有的排课(有效)
            $daoSchedule = new Dao_Schedule();
            $p1 = array(
                "area_operator" => $profile['area_operator'],
            );
            $c1 = array(
                "state" => Service_Data_Schedule::SCHEDULE_ABLE,
                "group_id" => $id,
            );
            $ret = $daoSchedule->updateByConds($c1, $p1);
            if ($ret == false) {
                $this->daoGroup->rollback();
                return false;
            }

            $this->daoGroup->commit();
            return true;
        }
        return $this->daoGroup->updateByConds(array('id'=>$id), $profile);
    }

    // 删除
    public function delete ($id) {
        return $this->daoGroup->deleteByConds(array('id'=>$id));
    }

    // 列表
    public function getListByConds($conds, $field = array(), $indexs = null, $appends = null) {
        $field = empty($field) || !is_array($field) ? $this->daoGroup->arrFieldsMap : $field;
        $lists = $this->daoGroup->getListByConds($conds, $field, $indexs, $appends);
        if (empty($lists)) {
            return array();
        }
        return $lists;
    }

    // 单独一项
    public function getRecordByConds($conds, $field = array(), $indexs = null, $appends = null) {
        $field = empty($field) || !is_array($field) ? $this->daoGroup->arrFieldsMap : $field;
        $Record = $this->daoGroup->getRecordByConds($conds, $field, $indexs, $appends);
        if (empty($Record)) {
            return array();
        }
        return $Record;
    }

    public function getTotalByConds($conds) {
        return  $this->daoGroup->getCntByConds($conds);
    }
}