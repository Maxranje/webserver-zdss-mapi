<?php

class Service_Data_Meta {

    private $daoMeta ;

    public function __construct() {
        $this->daoMeta = new Dao_Meta () ;
    }

    public function getMetaById ($id) {
        return $this->daoMeta->getRecordByConds(array('id' => $id), $this->daoMeta->arrFieldsMap);
    }
}