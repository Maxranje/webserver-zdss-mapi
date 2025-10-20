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
    
    public function getQidByTagAndLevel ($tagIds, $levels, $questionCnt = 30) {
        $conds = array(
            sprintf("tag_id in (%s)", implode(",",$tagIds)),
        );
        $limitTotal = 300;
        if (!empty($levels)) {
            $conds[] = sprintf("level in (%s)", implode(",",$levels));
        } else {
            $l2 = intval($questionCnt * 0.4);
            $l3 = intval($questionCnt * 0.4);
            $l4 = $questionCnt - $l2 - $l3;
            $limitTotal = 500;
        }
        $appends = array(
            "limit " . $limitTotal,
        );
        $lists = $this->getListByConds($conds, array(), null, $appends);
        if (empty($lists)) {
            return array();
        }
        shuffle($lists);
        $data = array();
        if (!empty($l2) && !empty($l3) && !empty($l4)) {
            foreach ($lists as $i => $item) {
                if ($l2 > 0 && $item["level"] == 2) {
                    $l2--;
                    $data[] = $item;
                    unset($lists[$i]);
                } else if ($l3 > 0 && $item["level"] == 3) {
                    $l3--;
                    $data[] = $item;
                    unset($lists[$i]);
                } else if ($l4 > 0 && $item["level"] == 4) {
                    $l4--;
                    $data[] = $item;
                    unset($lists[$i]);
                } 
            }
            if (count($data) < $questionCnt) {
                $lists = array_values($lists);
                $data = array_merge($data, array_slice($lists, 0, $questionCnt - count($data)));
            }
        } else {
            $data = $lists;
        }
        return array_slice($data, 0, $questionCnt);
    }
    

    public function getListByConds ($conds, $field = array(), $indexs = null, $appends = null) {
        $field = empty($field) || !is_array($field) ? $this->daoQuestiontag->arrFieldsMap : $field;
        return $this->daoQuestiontag->getListByConds($conds, $field, $indexs, $appends);
    }

    public function getTagTotalByConds ($conds) {
        return $this->daoQuestiontag->getCntByConds($conds);
    }
}