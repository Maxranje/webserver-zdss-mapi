<?php
// 留学计划
class Service_Data_Abroadplan {

    private $daoAbroadplan ;
    
    public function __construct() {
        $this->daoAbroadplan = new Dao_Abroadplan () ;
    }

    // 根据ID获取班级信息
    public function getAbroadplanById ($id){
        $arrConds = array(
            'id'  => $id,
        );

        $arrFields = $this->daoAbroadplan->arrFieldsMap;

        $Abroadplan = $this->daoAbroadplan->getRecordByConds($arrConds, $arrFields);
        if (empty($Abroadplan)) {
            return array();
        }

        return $Abroadplan;
    }

    // 根据IDs获取班级信息
    public function getAbroadplanByIds ($ids){
        $arrConds = array(
            sprintf("id in (%s)", implode(",", $ids))
        );

        $arrFields = $this->daoAbroadplan->arrFieldsMap;

        $Abroadplan = $this->daoAbroadplan->getListByConds($arrConds, $arrFields);
        if (empty($Abroadplan)) {
            return array();
        }

        return $Abroadplan;
    }   

    // 创建
    public function create ($profile) {
        return $this->daoAbroadplan->insertRecords($profile);
    }

    // 修改
    public function update ($id, $profile) {
        return $this->daoAbroadplan->updateByConds(array("id" => $id), $profile);
    }

    // 删除
    public function delete ($id) {
        return $this->daoAbroadplan->deleteByConds(array('id'=>$id));
    }

    // 列表
    public function getListByConds($conds, $field = array(), $indexs = null, $appends = null) {
        $field = empty($field) || !is_array($field) ? $this->daoAbroadplan->arrFieldsMap : $field;
        $lists = $this->daoAbroadplan->getListByConds($conds, $field, $indexs, $appends);
        if (empty($lists)) {
            return array();
        }
        return $lists;
    }

    // 单独一项
    public function getRecordByConds($conds, $field = array(), $indexs = null, $appends = null) {
        $field = empty($field) || !is_array($field) ? $this->daoAbroadplan->arrFieldsMap : $field;
        $Record = $this->daoAbroadplan->getRecordByConds($conds, $field, $indexs, $appends);
        if (empty($Record)) {
            return array();
        }
        return $Record;
    }

    public function getTotalByConds($conds) {
        return  $this->daoAbroadplan->getCntByConds($conds);
    }
}