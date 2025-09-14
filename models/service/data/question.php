<?php

class Service_Data_Question {

    // 类型
    const QUESTION_TYPE_RAIDO       = 1;
    const QUESTION_TYPE_CHECKBOX    = 2;
    const QUESTION_TYPE_CHECK       = 3;
    const QUESTION_TYPE_FILL        = 4;
    const QUESTION_TYPE_SIMPLEWRITE = 5;
    const QUESTION_TYPE_WRITE       = 6;
    const QUESTION_TYPE_LISTEN      = 7;
    const QUESTION_TYPE_SPEAK       = 8;
    const QUESTION_TYPE_MAP         = array(1,2,3,4,5,6,7,8);
    const QUESTION_TYPE_MAP_INFO    = array(
        1 => "单选题",
        2 => "多选题",
        3 => "判断题",
        4 => "填空题",
        5 => "简答题",
        6 => "写作题",
        7 => "听力题",
        8 => "口语题",
    );

    // 难度
    const QUESTION_LEVEL_V1         = 1;
    const QUESTION_LEVEL_V2         = 2;
    const QUESTION_LEVEL_V3         = 3;
    const QUESTION_LEVEL_V4         = 4;
    const QUESTION_LEVEL_V5         = 5;
    const QUESTION_LEVEL_MAP        = array(1,2,3,4,5);
    const QUESTION_LEVEL_MAP_INFO   = array(
        1 => "非常简单",
        2 => "简单",
        3 => "一般",
        4 => "较难",
        5 => "困难",
    );    

    // 对错
    const QUESTION_CHECK_TRUE       = 1;
    const QUESTION_CHECK_FALSE      = 2;

    // 一些特定标识
    const QUESTION_FILL_UNDERLINE   = "_____";

    // 选择题前缀
    const QUESTION_PREFIX = array(
        "A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T"
    );

    private $daoQuestion ;
    private $daoAnswer;
    private $daoMeta;
    private $daoQuestionTag;

    public function __construct() {
        $this->daoQuestion = new Dao_Question () ;
        $this->daoAnswer = new Dao_Answer();
        $this->daoMeta = new Dao_Meta();
        $this->daoQuestionTag = new Dao_QuestionTag();
    }

    public function getQuestionById ($qid) {
        $arrConds = array(
            'qid'  => $qid,
        );

        $question = $this->daoQuestion->getRecordByConds($arrConds, $this->daoQuestion->arrFieldsMap);
        if (empty($question)) {
            return array();
        }
        return $question;
    }

    public function getQuestionByIds ($qids, $simple = false) {
        $arrConds = array(
            sprintf("qid in (%s)", implode(",", $qids))
        );

        $field = $simple ? $this->daoQuestion->simpleFieldsMap : $this->daoQuestion->arrFieldsMap;
        $questions = $this->daoQuestion->getListByConds($arrConds, $field);
        if (empty($questions)) {
            return array();
        }
        return $questions;
    }

    public function getQuestionByParentId ($qid) {
        $arrConds = array(
            "parent_id" => $qid,
        );

        $questions = $this->daoQuestion->getListByConds($arrConds, $this->daoQuestion->arrFieldsMap);
        if (empty($questions)) {
            return array();
        }
        return $questions;
    }    

    // 创建试题
    public function createBatch ($profiles) {
        $this->daoQuestion->startTransaction();

        // 对内容添加
        foreach ($profiles as $profile) {
            $preMeta = $profile['pre_meta'];
            $questions = $profile['questions'];

            $metaId = $parentId = 0;
            // 更新前置材料
            if (!empty($preMeta["meta"])) {
                $metaProfile = array(
                    "content" => $preMeta["meta"],
                    "operator" => OPERATOR,
                    "update_time" => time()
                );
                $ret = $this->daoMeta->insertRecords($metaProfile);
                if ($ret == false) {
                    $this->daoQuestion->rollback();
                    return false;
                }
                $metaId = $this->daoMeta->getInsertId();
                if ($metaId <= 0) {
                    $this->daoQuestion->rollback();
                    return false;            
                }
                $metaId = intval($metaId);
            }   

            // 如果是组题,
            if  ($preMeta['is_group']) {
                $qProfile = array (
                    "content"       => "",
                    "explan"        => "",
                    "is_coll"       => 1,
                    "pre_meta_id"   => $metaId,
                    "description"   => $preMeta["parent_desc"],
                    "create_time"   => time(),
                    "update_time"   => time(),
                    "operator"      => OPERATOR,
                );
                $ret = $this->daoQuestion->insertRecords($qProfile);
                if ($ret == false) {
                    $this->daoQuestion->rollback();
                    return false;
                }

                $parentId = $this->daoQuestion->getInsertId();
                if ($parentId <= 0) {
                    $this->daoQuestion->rollback();
                    return false;            
                }
                $parentId = intval($parentId);                
            }

            // 试题插入
            foreach ($questions as $q) {
                $qProfile = array (
                    "type"          => $q['type'],
                    "level"         => $q['level'],
                    "score"         => $q["score"],
                    "subject_id"    => $q['subject_id'],
                    "content"       => $q["content"],
                    "explan"        => $q["explan"],
                    "description"   => $q['description'],
                    "pre_meta_id"   => $metaId,
                    'parent_id'     => $parentId,
                    "audio"         => $q["audio"],
                    "create_time"   => time(),
                    "update_time"   => time(),
                    "operator"      => OPERATOR,
                );
                $ret = $this->daoQuestion->insertRecords($qProfile);
                if ($ret == false) {
                    $this->daoQuestion->rollback();
                    return false;
                }

                $qid = $this->daoQuestion->getInsertId();
                if ($qid <= 0) {
                    $this->daoQuestion->rollback();
                    return false;            
                }
                $qid = intval($qid);

                // 答案入库
                if (!empty($q["answer"])) {
                    foreach ($q["answer"] as $v) {
                        $isCorrect = (isset($v["is_answer"]) && $v["is_answer"] == 1) || $q["type"] == self::QUESTION_TYPE_FILL;
                        $answer = array(               
                            "qid"           => $qid,
                            "type"          => $q["type"],
                            "content"       => $v["answer_content"],
                            "parent_id"     => $parentId,
                            "is_correct"    => $isCorrect ? 1 : 0, 
                            "operator"      => OPERATOR,
                            "create_time"   => time(),
                            "update_time"   => time(),
                        );
                        $ret = $this->daoAnswer->insertRecords($answer);
                        if ($ret == false) {
                            $this->daoQuestion->rollback();
                            return false;                       
                        }                  
                    }
                } 

                // 标签关联入库
                if (!empty($q["tag_ids"])) {
                    foreach ($q["tag_ids"] as $v) {
                        $tProfile = array(
                            "qid"           => intval($qid),
                            "tag_id"        => intval($v),
                            "update_time"   => time(),
                        );
                        $ret = $this->daoQuestionTag->insertRecords($tProfile);
                        if ($ret == false) {
                            $this->daoQuestion->rollback();
                            return false;                       
                        }
                    }
                }
            }
        }
        
        $this->daoQuestion->commit();
        return true;
    }


    // 删除试题
    public function deleteBatch ($qid) {
        $this->daoQuestion->startTransaction();

        // 删tag
        $conds = array(
            "qid" => $qid,
        );
        $ret = $this->daoQuestionTag->deleteByConds($conds);
        if ($ret == false) {
            $this->daoQuestion->rollback();
            return false;     
        }

        // 删答案
        $ret = $this->daoAnswer->deleteByConds($conds);
        if ($ret == false) {
            $this->daoQuestion->rollback();
            return false;     
        }   

        // 删题
        $ret = $this->daoQuestion->deleteByConds($conds);
        if ($ret == false) {
            $this->daoQuestion->rollback();
            return false;     
        }

        // 删子体
        $ret = $this->daoQuestion->deleteByConds(array("parent_id" => $qid));
        if ($ret == false) {
            $this->daoQuestion->rollback();
            return false;     
        }        

        $this->daoQuestion->commit();
        return true;
    }    

    // 列表
    public function getListByConds($conds, $field = array(), $indexs = null, $appends = null) {
        $field = empty($field) || !is_array($field) ? $this->daoQuestion->arrFieldsMap : $field;
        $lists = $this->daoQuestion->getListByConds($conds, $field, $indexs, $appends);
        if (empty($lists)) {
            return array();
        }
        return $lists;
    }

    // 单独一项
    public function getRecordByConds($conds, $field = array(), $indexs = null, $appends = null) {
        $field = empty($field) || !is_array($field) ? $this->daoQuestion->arrFieldsMap : $field;
        $Record = $this->daoQuestion->getRecordByConds($conds, $field, $indexs, $appends);
        if (empty($Record)) {
            return array();
        }
        return $Record;
    }

    public function getTotalByConds($conds) {
        return  $this->daoQuestion->getCntByConds($conds);
    }

    // reqParam 中meta和question是一个组, 每次检查一组内容
    public function checkReqQuestionParam ($reqParam) {
        $questions = $reqParam["questions"];
        $preMeta = $reqParam["pre_meta"];

        if (empty($questions) || empty($preMeta)) {
            throw new Zy_Core_Exception(405, "操作失败, 请求参数缺失");
        }

        if (!empty($preMeta["is_group"]) && empty($preMeta["meta"])) {
            throw new Zy_Core_Exception(405, "操作失败, 题目组必须要有前置材料");
        }

        if (!empty($preMeta["is_group"]) && empty($preMeta["parent_desc"])) {
            throw new Zy_Core_Exception(405, "操作失败, 题目组必须要有简述");
        }        

        if (!empty($preMeta["meta"]) && !Zy_Helper_Utils::validateString($preMeta["meta"], 1, 10000)) {
            throw new Zy_Core_Exception(405, "操作失败, 前置材料长度限定10000字符内");
        }

        if (!empty($preMeta["parent_desc"]) && !Zy_Helper_Utils::validateString($preMeta["parent_desc"], 1, 200)) {
            throw new Zy_Core_Exception(405, "操作失败, 题目组简述长度限定200字符内");
        }        

        $subjectIds = $tagIds = array();
        foreach ($questions as $i => $question) {
            if (!in_array($question["type"], self::QUESTION_TYPE_MAP)) {
                throw new Zy_Core_Exception(405, "操作失败, 类型检测失败, 请重新确认题目类型");
            }

            if (!in_array($question["level"], self::QUESTION_LEVEL_MAP)) {
                throw new Zy_Core_Exception(405, "操作失败, 难度检测失败, 请重新确认题目难度");
            }

            if ($question["score"] <= 0 || $question["score"] >= 100) {
                throw new Zy_Core_Exception(405, "操作失败, 分值检测失败, 请重新确认题目分值, 1-100之间");
            }            

            if (empty($preMeta["is_group"]) && empty($question["description"])) {
                throw new Zy_Core_Exception(405, "操作失败, 单项题必须有描述");
            }

            if (!empty($question["description"]) && !Zy_Helper_Utils::validateString($question["description"], 1, 200)) {
                throw new Zy_Core_Exception(405, "操作失败, 单项题描述长度200字符内");
            }

            if (!Zy_Helper_Utils::validateString($question["content"], 1, 500)) {
                throw new Zy_Core_Exception(405, "操作失败, 题干长度500字符内, 如果想设置更多内容, 请采用前置材料填写");
            } 

            if (!empty($question["explan"]) && !Zy_Helper_Utils::validateString($question["content"], 1, 500)) {
                throw new Zy_Core_Exception(405, "操作失败, 解析长度500字符内");
            }             

            if ($question["type"] == self::QUESTION_TYPE_RAIDO) {
                if (empty($question["radio"])) {
                    throw new Zy_Core_Exception(405, "操作失败, 单选题必须要有选项");
                }
                $correct = 0;
                foreach ($question["radio"] as $k => $v) {
                    if (!isset($v["answer_content"])) {
                        throw new Zy_Core_Exception(405, sprintf("操作失败, 第%s选项没有答案描述", $k));
                    }
                    if (!Zy_Helper_Utils::validateString($v["answer_content"], 0, 100)) {
                        throw new Zy_Core_Exception(405, sprintf("操作失败, 第%s选项答案描述内容包含非法字符或长度超过100个字符", $k));
                    }
                    if (isset($v["is_answer"]) && $v["is_answer"] == 1) {
                        $correct++;
                    }
                    $v["answer_id"] = empty($v["answer_id"]) ? 0 : intval($v["answer_id"]);
                }
                if ($correct != 1) {
                    throw new Zy_Core_Exception(405, sprintf("操作失败, 单选题没有配置正确答案或配置多个正确答案"));
                }
                $question["answer"] = $question["radio"];
            }

            // 多选题
            if ($question["type"] == self::QUESTION_TYPE_CHECKBOX) {
                if (empty($question["checkbox"])) {
                    throw new Zy_Core_Exception(405, "操作失败, 多选题必须要有选项");
                }
                $correct = 0;
                foreach ($question["checkbox"] as $k => $v) {
                    if (!isset($v["answer_content"])) {
                        throw new Zy_Core_Exception(405, sprintf("操作失败, 第%s选项没有答案描述", $k));
                    }
                    if (!Zy_Helper_Utils::validateString($v["answer_content"], 0, 100)) {
                        throw new Zy_Core_Exception(405, sprintf("操作失败, 第%s选项答案描述内容包含非法字符或长度超过100个字符", $k));
                    }
                    if (isset($v["is_answer"]) && $v["is_answer"] == 1) {
                        $correct++;
                    }
                    $v["answer_id"] = empty($v["answer_id"]) ? 0 : intval($v["answer_id"]);
                }
                if ($correct <= 0) {
                    throw new Zy_Core_Exception(405, sprintf("操作失败, 多选题没有配置正确答案"));
                }
                $question["answer"] = $question["checkbox"];
            }

            // 判断题
            if ($question["type"] == self::QUESTION_TYPE_CHECK) {
                if (empty($question["check"]) && count($question["check"]) != 2) {
                    throw new Zy_Core_Exception(405, "操作失败, 判断题必须要有且只有2个选项");
                }
                $correct = 0;
                foreach ($question["check"] as $k => $v) {
                    if (!isset($v["answer_content"])) {
                        throw new Zy_Core_Exception(405, sprintf("操作失败, 第%s选项没有答案描述", $k));
                    }
                    if (!Zy_Helper_Utils::validateString($v["answer_content"], 0, 100)) {
                        throw new Zy_Core_Exception(405, sprintf("操作失败, 第%s选项答案描述内容包含非法字符或长度超过100个字符", $k));
                    }
                    if (isset($v["is_answer"]) && $v["is_answer"] == 1) {
                        $correct++;
                    }
                    $v["answer_id"] = empty($v["answer_id"]) ? 0 : intval($v["answer_id"]);
                }
                if ($correct != 1) {
                    throw new Zy_Core_Exception(405, sprintf("操作失败, 判断题只能配置一个正确答案"));
                }
                $question["answer"] = $question["check"];
            }     
            
            // 填空题
            if ($question["type"] == self::QUESTION_TYPE_FILL) {
                if (empty($question["fill"])) {
                    throw new Zy_Core_Exception(405, "操作失败, 填空题必须要有答案");
                }
                if (substr_count($question["content"], self::QUESTION_FILL_UNDERLINE) != count($question["fill"])) {
                    throw new Zy_Core_Exception(405, "操作失败, 题干中填充部分(5个下划线)数量 与答案数量不匹配");
                }
                foreach ($question["fill"] as $k => $v) {
                    if (!isset($v["answer_content"])) {
                        throw new Zy_Core_Exception(405, sprintf("操作失败, 第%s选项没有答案描述", $k));
                    }
                    if (!Zy_Helper_Utils::validateString($v["answer_content"], 0, 100)) {
                        throw new Zy_Core_Exception(405, sprintf("操作失败, 第%s选项答案描述内容包含非法字符或长度超过100个字符", $k));
                    }
                    $v["answer_id"] = empty($v["answer_id"]) ? 0 : intval($v["answer_id"]);
                }
                $question["answer"] = $question["fill"];
            }  

            // 听力
            if ($question["type"] == self::QUESTION_TYPE_LISTEN) {
                if (empty($question["audio"])) {
                    throw new Zy_Core_Exception(405, "操作失败, 听力必须配置音频地址");
                }
                if (!Zy_Helper_Utils::validateStringHttp($question["audio"])) {
                    throw new Zy_Core_Exception(405, "操作失败, 音频地址不是一个有效http地址");
                }
            }

            if ($question["subject_id"] > 0) {
                $subjectIds[] = $question["subject_id"];
            }
            if (count($question["tag_ids"]) > 3) {
                throw new Zy_Core_Exception(405, "操作失败, 每个试题最多3个标签");
            }
            if (!empty($question["tag_ids"])) {
                $tagIds = array_merge($tagIds, $question["tag_ids"]);
            }

            $questions[$i] = $question;
        }

        // 检测subject
        if (count($subjectIds) > 0) {
            $serviceData = new Service_Data_Subject();
            $subjectInfos = $serviceData->getSubjectByIds($subjectIds);
            if (empty($subjectInfos) || count($subjectInfos) != count($subjectIds)) {
                throw new Zy_Core_Exception(405, "操作失败, 部分科目信息不存在或已失效, 请每项对比检查");
            }
        }

        // 检查tags
        if (count($tagIds) > 0) {
            $serviceData = new Service_Data_tag();
            $tagInfos = $serviceData->getTagByIds($tagIds);
            if (empty($tagInfos) || count($tagInfos) != count($tagIds)) {
                throw new Zy_Core_Exception(405, "操作失败, 标签信息获取失败, 请刷新重新配置");
            }  
        }

        return array(
            "questions" => $questions,
            "pre_meta"  => $preMeta,
        );
    } 
}