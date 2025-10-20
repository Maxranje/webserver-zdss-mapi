<?php

class Service_Data_Meta {

    private $daoMeta ;

    const META_TYPE_TEXT    = 1;
    const META_TYPE_AUDIO   = 2;
    const META_TYPE_MAP     = [1,2];

    public function __construct() {
        $this->daoMeta = new Dao_Meta () ;
    }

    public function getMetaById ($id) {
        return $this->daoMeta->getRecordByConds(array('id' => $id), $this->daoMeta->arrFieldsMap);
    }

    public function getMetaByIds ($ids) {
        return $this->daoMeta->getListByConds(array(
            sprintf("id in (%s)", implode(",", $ids))
        ), $this->daoMeta->arrFieldsMap);
    }    
}