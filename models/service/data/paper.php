<?php

class Service_Data_Paper {

    const PAPER_TYPE_NORMAL = 1;
    const PAPER_TYPE_ASSESS = 2; // 入学评估
    const PAPER_TYPE_WORD   = 3; // 单词
    const PAPER_TYPE_MAP    = [1,2,3];

    private $daoPaper ;
    private $daoPaperSource;
    private $daoPaperQuestions;

    public function __construct() {
        $this->daoPaper = new Dao_Paper () ;
        $this->daoPaperSource = new Dao_PaperSource();
        $this->daoPaperQuestions = new Dao_PaperQuestion();

    }

    public function getPaperById ($pid) {
        $arrConds = array(
            'pid'  => $pid,
        );

        $Paper = $this->daoPaper->getRecordByConds($arrConds, $this->daoPaper->arrFieldsMap);
        if (empty($Paper)) {
            return array();
        }
        return $Paper;
    }

    public function getPaperByIds ($pids) {
        $arrConds = array(
            sprintf("pid in (%s)", implode(",", $pids))
        );

        $Paper = $this->daoPaper->getListByConds($arrConds, $this->daoPaper->arrFieldsMap);
        if (empty($Paper)) {
            return array();
        }
        return $Paper;
    }

    // 获取试卷试题信息
    public function getPaperQuestions ($pid) {
        $arrConds = array(
            "pid" => $pid,
        );

        $Lists = $this->daoPaperQuestions->getListByConds($arrConds, $this->daoPaperQuestions->arrFieldsMap);
        if (empty($Lists)) {
            return array();
        }
        return $Lists;
    }    

    // 获取试卷试题id
    public function getPaperQuestionIds ($pid) {
        $arrConds = array(
            "pid" => $pid,
        );

        $Lists = $this->daoPaperQuestions->getListByConds($arrConds, $this->daoPaperQuestions->arrFieldsMap);
        if (empty($Lists)) {
            return array();
        }
        return Zy_Helper_Utils::arrayInt($Lists, "qid");
    }        

    // 根据qid获取pid
    public function getPaperIdsByQids ($qids, $needInfo = false) {
        $arrConds = array(
            sprintf("qid in (%s)", implode(",", $qids))
        );

        $Lists = $this->daoPaperQuestions->getListByConds($arrConds, $this->daoPaperQuestions->arrFieldsMap);
        if (empty($Lists)) {
            return array();
        }

        if (!$needInfo) {
            return Zy_Helper_Utils::arrayInt($Lists, "pid");
        }

        $ret = array();
        foreach ($Lists as $item) {
            if (!isset($ret[$item["qid"]])) {
                $ret[$item["qid"]] = array();
            }
            $ret[$item["qid"]][] = $item;
        }
        return $ret;
    }     
    
    // 根据qid获取pid
    public function getSourceByPaperIds ($pids) {
        $arrConds = array(
            sprintf("pid in (%s)", implode(",", $pids))
        );

        $Lists = $this->daoPaperSource->getListByConds($arrConds, $this->daoPaperSource->arrFieldsMap);
        if (empty($Lists)) {
            return array();
        }

        $ret = array();
        foreach ($Lists as $v) {
            if (!isset($ret[$v["pid"]])) {
                $ret[$v['pid']] = array();
            }
            $ret[$v['pid']][] = $v;
        }
        return $ret;
    }         

    // 创建
    public function create ($profile) {
        $this->daoPaper->startTransaction();

        $paperProfile = array(
            "title" => $profile["title"],
            "subject_id" => $profile["subject_id"],
            "type" => $profile["type"],
            "operator" => OPERATOR,
            "remark" => $profile["remark"],
            "update_time" => time(),
            "create_time" => time(),   
        );
        $ret = $this->daoPaper->insertRecords($paperProfile);
        if ($ret ==false) {
            $this->daoPaper->rollback();
            return false;
        }
        $pid = $this->daoPaper->getInsertId();
        if ($pid <= 0) {
            $this->daoPaper->rollback();
            return false;            
        }
        $pid = intval($pid);        

        // 更新来源信息
        if (!empty($profile["source_ids"])) {
            foreach ($profile["source_ids"] as $v) {
                $sourcrProfile = array(
                    "pid"           => intval($pid),
                    "source_id"     => intval($v),
                    "update_time"   => time(),
                );
                $ret = $this->daoPaperSource->insertRecords($sourcrProfile);
                if ($ret == false) {
                    $this->daoPaper->rollback();
                    return false;                       
                }
            }
        }
        $this->daoPaper->commit();
        return true;
    }

    // 更新
    public function update ($pid, $profile) {
        $this->daoPaper->startTransaction();

        $conds = array(
            "pid" => $pid,
        );
        $paperProfile = array(
            "title" => $profile["title"],
            "subject_id" => $profile["subject_id"],
            "type" => $profile["type"],
            "operator" => OPERATOR,
            "remark" => $profile["remark"],
            "update_time" => time(),
        );
        $ret = $this->daoPaper->updateByConds($conds, $paperProfile);
        if ($ret ==false) {
            $this->daoPaper->rollback();
            return false;
        }

        // 更新来源信息
        $ret = $this->daoPaperSource->deleteByConds($conds);
        if ($ret ==false) {
            $this->daoPaper->rollback();
            return false;
        }
        if (!empty($profile["source_ids"])) {
            foreach ($profile["source_ids"] as $v) {
                $sourcrProfile = array(
                    "pid"           => intval($pid),
                    "source_id"     => intval($v),
                    "update_time"   => time(),
                );
                $ret = $this->daoPaperSource->insertRecords($sourcrProfile);
                if ($ret == false) {
                    $this->daoPaper->rollback();
                    return false;                       
                }
            }
        }
        $this->daoPaper->commit();
        return true;
    }

    // 修改试题
    public function addQuestion ($profile) {
        $this->daoPaper->startTransaction(); 
        
        $questions = array_column($profile["questions"], null, "qid");
        
        // 添加来源信息
        if (!empty($profile["addQids"])) {    
            foreach ($profile["addQids"] as $v) {
                $pProfile = array(
                    "pid"     => intval($profile["pid"]),
                    "qid"     => intval($v),
                    "score"   => empty($questions[$v]["score"]) ? 0 : $questions[$v]["score"],
                    "update_time"   => time(),
                );
                $ret = $this->daoPaperQuestions->insertRecords($pProfile);
                if ($ret == false) {
                    $this->daoPaper->rollback();
                    return false;                       
                }
            }
        }
        // 删除来源信息
        if (!empty($profile["delQids"])) {    
            $conds = array(
                "pid = " . $profile["pid"],
                sprintf("qid in (%s)", implode(",", $profile["delQids"])),
            );
            $ret = $this->daoPaperQuestions->deleteByConds($conds);
            if ($ret == false) {
                $this->daoPaper->rollback();
                return false;                       
            }
        }     
        
        // 更新总分数
        $conds = array(
            "pid" => $profile["pid"],
        );
        $paperProfile = array(
            "total_score" => $profile["total_score"],
        );
        $ret = $this->daoPaper->updateByConds($conds, $paperProfile);
        if ($ret == false) {
            $this->daoPaper->rollback();
            return false;                       
        }        

        $this->daoPaper->commit();
        return true;
    }

    // 修改试题分值
    public function updateQuestionScore ($mapId, $score) {
        $conds = array(
            "id" => $mapId,
        );
        return $this->daoPaperQuestions->updateByConds($conds, array("score" => $score));
    }    
    
    // 删除
    public function delete ($pid) {
        $this->daoPaper->startTransaction();

        $conds = array(
            "pid" => $pid,
        );
        $ret = $this->daoPaperSource->deleteByConds($conds);
        if ($ret ==false) {
            $this->daoPaper->rollback();
            return false;
        }    

        // 删试题关联
        $ret = $this->daoPaperQuestions->deleteByConds($conds);
        if ($ret == false) {
            $this->daoPaper->rollback();
            return false;                       
        }

        // 删试卷自身
        $ret = $this->daoPaper->deleteByConds($conds);
        if ($ret == false) {
            $this->daoPaper->rollback();
            return false;                       
        }       
        
        // 删除source关联
        $ret = $this->daoPaperSource->deleteByConds($conds);
        if ($ret == false) {
            $this->daoPaper->rollback();
            return false;                       
        }   
        
        $this->daoPaper->commit();
        return true;
    }

    // 列表
    public function getListByConds($conds, $field = array(), $indexs = null, $appends = null) {
        $field = empty($field) || !is_array($field) ? $this->daoPaper->arrFieldsMap : $field;
        $lists = $this->daoPaper->getListByConds($conds, $field, $indexs, $appends);
        if (empty($lists)) {
            return array();
        }
        return $lists;
    }

    // 单独一项
    public function getRecordByConds($conds, $field = array(), $indexs = null, $appends = null) {
        $field = empty($field) || !is_array($field) ? $this->daoPaper->arrFieldsMap : $field;
        $Record = $this->daoPaper->getRecordByConds($conds, $field, $indexs, $appends);
        if (empty($Record)) {
            return array();
        }
        return $Record;
    }

    public function getTotalByConds($conds) {
        return  $this->daoPaper->getCntByConds($conds);
    }
}