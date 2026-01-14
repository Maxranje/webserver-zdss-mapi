<?php

class Service_Data_Tag {

    private $daoTag ;

    public function __construct() {
        $this->daoTag = new Dao_Tag () ;
    }

    // 获取科目信息
    public function getTagById ($id) {
        return $this->daoTag->getRecordByConds(array('id' => $id), $this->daoTag->arrFieldsMap);
    } 

    public function getTagByParentID ($id) {
        return $this->daoTag->getListByConds(array('parent_id' => $id), $this->daoTag->arrFieldsMap);
    } 

    public function getTagByTitle ($title) {
        return $this->daoTag->getRecordByConds(array('title' => $title), $this->daoTag->arrFieldsMap);
    } 

    public function getTagByIds ($ids) {
        return $this->daoTag->getListByConds(array(sprintf("id in (%s)", implode(",", $ids))), $this->daoTag->arrFieldsMap);
    } 

    public function getListByConds ($conds, $field = array(), $indexs = null, $appends = null) {
        $field = empty($field) || !is_array($field) ? $this->daoTag->arrFieldsMap : $field;
        return $this->daoTag->getListByConds($conds, $field, $indexs, $appends);
    }

    public function getTagTotalByConds ($conds) {
        return $this->daoTag->getCntByConds($conds);
    }

    public function createTag($profile) {
        return $this->daoTag->insertRecords($profile);
    }

    public function updateTag($id, $profile){
        return $this->daoTag->updateByConds(array('id' => $id), $profile);
    }

    public function deleteTag ($id) {
        $this->daoTag->startTransaction();
        //删除题库中
        $daoQuestionTag = new Dao_Questiontag();
        $conds = array(
            "tag_id" => $id,
        );
        $ret = $daoQuestionTag->deleteByConds($conds);
        if ($ret == false) {
            $this->daoTag->rollback();
            return false;
        }

        $ret = $this->daoTag->deleteByConds(array("id" => $id));
        if ($ret == false) {
            $this->daoTag->rollback();
            return false;
        }        
        $this->daoTag->commit();
        return true;
    }
}