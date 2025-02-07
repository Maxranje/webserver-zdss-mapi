<?php
// 留学计划
class Service_Data_Apackageconfirm {

    private $daoApackageconfirm ;
    
    public function __construct() {
        $this->daoApackageconfirm = new Dao_ApackageConfirm () ;
    }

    public function getConfirmById ($id){
        $arrFields = $this->daoApackageconfirm->arrFieldsMap;

        $data = $this->daoApackageconfirm->getRecordByConds(array("apackage_id" => $id), $arrFields);

        if (empty($data)) {
            return array();
        }
        if (!empty($data['content'])) {
            $data['content'] = json_decode($data['content'], true); 
        }
        return $data;
    }    

    public function getConfirmByIds ($ids){
        $arrFields = $this->daoApackageconfirm->arrFieldsMap;

        $data = $this->daoApackageconfirm->getListByConds(array(
            sprintf("apackage_id in (%s)", implode(",", $ids))
        ), $arrFields);

        if (empty($data)) {
            return array();
        }
        foreach ($data as &$item) {
            if (!empty($item['content'])) {
                $item['content'] = json_decode($item['content'], true);
            }
        }
        return $data;
    }  

    // 创建
    public function create ($profile) {
        return $this->daoApackageconfirm->insertRecords($profile);
    }

    // 修改
    public function update ($id, $profile) {
        return $this->daoApackageconfirm->updateByConds(array("id" => $id), $profile);
    }
}