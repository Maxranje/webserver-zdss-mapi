<?php
// 留学计划
class Service_Data_Abroadplanconfirm {

    private $daoconfirm ;
    
    public function __construct() {
        $this->daoconfirm = new Dao_AbroadplanConfirm () ;
    }     

    public function getConfirmById ($id){
        $arrFields = $this->daoconfirm->arrFieldsMap;

        $data = $this->daoconfirm->getRecordByConds(array("abroadplan_id" => $id), $arrFields);

        if (empty($data)) {
            return array();
        }
        if (!empty($data['content'])) {
            $data['content'] = json_decode($data['content'], true); 
        }
        return $data;
    }    

    public function getConfirmByIds ($ids){
        $arrFields = $this->daoconfirm->arrFieldsMap;

        $data = $this->daoconfirm->getListByConds(array(
            sprintf("abroadplan_id in (%s)", implode(",", $ids))
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
        return $this->daoconfirm->insertRecords($profile);
    }

    // 修改
    public function update ($id, $profile) {
        return $this->daoconfirm->updateByConds(array("id" => $id), $profile);
    }
}