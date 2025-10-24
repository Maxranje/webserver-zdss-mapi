<?php

class Service_Data_Questiontag {

    private $daoQuestiontag ;

    public function __construct() {
        $this->daoQuestiontag = new Dao_Questiontag () ;
    }

    // 获取科目信息
    public function getQidByTagids ($tagIds) {
        $ret = $this->getListByConds(array(sprintf("tag_id in (%s)", implode(",",$tagIds))));
        if (empty($ret)) {
            return array();
        }
        return Zy_Helper_Utils::arrayInt($ret, "qid");
    } 

    public function getTagidsByQids ($qids) {
        $ret = $this->getListByConds(array(sprintf("qid in (%s)", implode(",",$qids))));
        if (empty($ret)) {
            return array();
        }
        $result = array();
        foreach ($ret as $v) {
            if (!isset($result[$v["qid"]])) {
                $result[$v['qid']] = array();
            }
            $result[$v['qid']][] = intval($v['tag_id']);
        }
        return $result;
    }  

    public function getTagByQids ($qids, $simple = false) {
        $ret = $this->getListByConds(array(sprintf("qid in (%s)", implode(",",$qids))));
        if (empty($ret)) {
            return array();
        }
        $tagIds = Zy_Helper_Utils::arrayInt($ret, "tag_id");

        $daoTag = new Dao_Tag();
        $conds = array(
            sprintf("id in (%s)", implode(",", $tagIds))
        );
        $fileds = array(
            "id",
            "title",
        );
        if ($simple) {
            $fileds[] = "description";
        }
        $tagInfos = $daoTag->getListByConds($conds, $fileds);
        if (empty($tagInfos)) {
            return array();
        }
        $tagInfos = array_column($tagInfos, null, "id");

        $result = array();
        foreach ($ret as $v) {
            if (!isset($result[$v["qid"]])) {
                $result[$v['qid']] = array();
            }
            if (empty($tagInfos[$v["tag_id"]])) {
                continue;
            }
            $result[$v['qid']][] = $tagInfos[$v["tag_id"]];
        }
        return $result;
    } 

    public function getListByConds ($conds, $field = array(), $indexs = null, $appends = null) {
        $field = empty($field) || !is_array($field) ? $this->daoQuestiontag->arrFieldsMap : $field;
        return $this->daoQuestiontag->getListByConds($conds, $field, $indexs, $appends);
    }

    public function getTagTotalByConds ($conds) {
        return $this->daoQuestiontag->getCntByConds($conds);
    }
}