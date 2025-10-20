<?php

class Service_Data_Exam {

    private $daoExam ;
    private $daoExamStudent;
    private $daoExamAnswer;

    // 模考状态, 1:待开始, 2:完成, 5进行中, 4强制结束
    const EXAM_STATUS_PENDING       = 1;
    const EXAM_STATUS_COMPLETE      = 2;
    const EXAM_STATUS_ONGOING       = 5;
    const EXAM_STATUS_TERMINATED    = 4;

    // 考生模考状态, 1:待开始, 5, 进行中. 2 完成, 4:强制结束, 7:审核, 8:结束
    const EXAM_STUDENT_STATUS_PENDING       = 1;
    const EXAM_STUDENT_STATUS_ONGOING       = 5;
    const EXAM_STUDENT_STATUS_COMPLETE      = 2;
    const EXAM_STUDENT_STATUS_TERMINATED    = 4;
    const EXAM_STUDENT_STATUS_REVIEWING     = 7;
    const EXAM_STUDENT_STATUS_FINISHED      = 8;

    const SIGNLE_LEVEL_CNT = 10;

    public function __construct() {
        $this->daoExam = new Dao_Exam () ;
        $this->daoExamStudent = new Dao_ExamStudent () ;
        $this->daoExamAnswer = new Dao_ExamAnswer () ;
    }

    public function getExamById ($id) {
        $arrConds = array(
            'id'  => $id,
        );

        $data = $this->daoExam->getRecordByConds($arrConds, $this->daoExam->arrFieldsMap);
        if (empty($data)) {
            return array();
        }

        return $data;
    }

    public function getExamByIndentify ($indentify) {
        $arrConds = array(
            'indentify'  => $indentify,
        );

        $data = $this->daoExam->getRecordByConds($arrConds, $this->daoExam->arrFieldsMap);
        if (empty($data)) {
            return array();
        }

        return $data;
    }

    public function getExamByIds ($ids) {
        $arrConds = array(
            sprintf("id in (%s)", implode(",", $ids))
        );

        $data = $this->daoExam->getListByConds($arrConds, $this->daoExam->arrFieldsMap);
        if (empty($data)) {
            return array();
        }

        return $data;
    }

    public function getExamByTeacherUid ($uid) {
        $arrConds = array(
            "teacher_uid" => $uid,
        );

        $data = $this->daoExam->getListByConds($arrConds, $this->daoExam->arrFieldsMap);
        if (empty($data)) {
            return array();
        }
        return $data;
    }    

    public function getExamByPid ($pid) {
        $arrConds = array(
            sprintf("pid = %d", $pid)
        );

        $data = $this->daoExam->getListByConds($arrConds, $this->daoExam->arrFieldsMap);
        if (empty($data)) {
            return array();
        }

        return $data;
    }    

    // get student record by examid and studentuid
    public function getExamStudent ($examId, $uid) {
        $arrConds = array(
            "exam_id" => $examId,
            "student_uid" => $uid,
        );

        $ret = $this->daoExamStudent->getRecordByConds($arrConds, $this->daoExamStudent->arrFieldsMap);
        if (empty($ret)) {
            return array();
        }
        return $ret;
    }    

    // 根据student_uid获取模考信息
    public function getExamsBySuid ($uid) {
        $arrConds = array(
            "student_uid" => $uid,
        );

        $data = $this->daoExamStudent->getListByConds($arrConds, $this->daoExamStudent->arrFieldsMap);
        if (empty($data)) {
            return array();
        }
        return $data;
    }

    // 根据student_uid获取模考数
    public function getExamCntBySuid ($uid) {
        $arrConds = array(
            "student_uid" => $uid,
        );

        return $this->daoExamStudent->getCntByConds($arrConds, array("count(id) as count"));
    }    

    // 根据examid获取学生记录
    public function getStudentsByExamId ($examIds) {
        $arrConds = array(
            sprintf("exam_id in (%s)", implode(",", $examIds))
        );

        $data = $this->daoExamStudent->getListByConds($arrConds, $this->daoExamStudent->arrFieldsMap);
        if (empty($data)) {
            return array();
        }

        $ret = array();
        foreach ($data as $v) {
            if (!isset($ret[$v["exam_id"]])) {
                $ret[$v["exam_id"]] = array();
            }
            $ret[$v["exam_id"]][] = $v;
        }

        return $ret;
    }

    // get student answer cnt by exam id
    public function getAnswerCntByExamId ($examId) {
        $arrConds = array(
            "exam_id" => $examId,
        );

        $data = $this->daoExamAnswer->getListByConds($arrConds, array(
            "count(qid) as count", 
            "student_uid",
        ), null, array("group by student_uid,qid"));
        if (empty($data)) {
            return array();
        }        

        $ret = array();
        foreach ($data as $v) {
            if (!isset($ret[$v["student_uid"]])) {
                $ret[$v["student_uid"]] = 0;
            }
            $ret[$v["student_uid"]]++;
        }
        return $ret;        
    }

    // get student answer by exam id
    public function getAnswerByExamId ($examId, $studentUid) {
        $arrConds = array(
            sprintf("exam_id = %d", $examId),
            sprintf("student_uid = %d", $studentUid),
        );    

        $data = $this->daoExamAnswer->getListByConds($arrConds, $this->daoExamAnswer->arrFieldsMap);
        if (empty($data)) {
            return array();
        }
        return $data;
    }  

    // 获取一个学员多次考试的结果, 包括正确度
    public function getMuiltExamAnswerDetail ($studentExam, $studetnAnswer) {
        // 简单格式化, 把题放到每个exam中
        $studentExam = array_column($studentExam, null, "exam_id");
        foreach ($studetnAnswer as $v) {
            if (!isset($studentExam[$v["exam_id"]])) {
                continue;
            }
            $studentExam[$v["exam_id"]]["studentAnswer"][] = $v;
        }
        $studentExam = array_values($studentExam);

        // 按照end_time 进行输出
        usort($studentExam, function($a, $b){
            return $a["end_time"] >= $b["end_time"] ? -1 : 1;
        });

        foreach ($studentExam as &$v) {
            // 客观题按实际情况,  主观题按是否给评分, 如果给了就是正确
            if (!empty($v["studentAnswer"])) {
                $qidAnswerMap = array();
                foreach ($v["studentAnswer"] as $vv) {
                    if (!isset($qidAnswerMap[$vv["qid"]])) {
                        $qidAnswerMap[$vv['qid']] = array("is_correct" => 1, "score" => $vv["score"], "type" => $vv["type"]);
                    }
                    if (in_array($vv["type"], Service_Data_Question::QUESTION_TYPE_SIMPLE_MAP)) {
                        if ($qidAnswerMap[$vv['qid']]["is_correct"] == 1) {
                            $qidAnswerMap[$vv['qid']]["is_correct"] = $vv["is_correct"] == 1 ? 1 : 0;
                        }
                    } else { // 主观题按是否给评分, 如果给了就是正确 不给就是错误
                        if ($qidAnswerMap[$vv['qid']]["score"] <= 0) {
                            $qidAnswerMap[$vv['qid']]["is_correct"] = 0;
                        }
                    }
                }
                $v["studentAnswerRet"] = $qidAnswerMap;
            } 
        } 
        return $studentExam;       
    }

    // 创建
    public function create ($profile) {       
        $this->daoExam->startTransaction();
        $paper = $profile["paper"];
        
        //  创建考试
        $examProfile = array(
            "indentify" => $profile["indentify"],
            "pid" => $profile["pid"],
            "paper_type" => $paper["type"],
            "total_score" => $paper["total_score"],
            "total_question" => $profile["total_question"],
            "start_time" => $profile["start_time"],
            "end_time" => $profile["end_time"],
            "expire_time" => $profile["expire_time"],
            "remark" => $profile["remark"],
            "teacher_uid" => $profile["teacher_uid"],
            "status" => self::EXAM_STATUS_PENDING,
            "operator" => OPERATOR,
            "update_time" => time(),
            "create_time" => time(),
        );
        $ret = $this->daoExam->insertRecords($examProfile);
        if ($ret == false) {
            $this->daoExam->rollback();
            return false;                       
        }    
        $examId = $this->daoExam->getInsertId();
        if ($examId <= 0) {
            $this->daoExam->rollback();
            return false;            
        }
        $examId = intval($examId);      
        
        // 更新试卷使用次数
        $daoPaper = new Dao_Paper();
        $ret = $daoPaper->updateByConds(array("pid" => $profile["pid"]), array(
            sprintf("frequency=frequency+%d", 1),
        ));
        if ($ret == false) {
            $this->daoExam->rollback();
            return false;
        }        
        
        // 关联考生
        foreach ($profile["student_uids"] as $v) {
            $mapProfile = array(
                "exam_id" => $examId,
                "pid" => $profile["pid"],
                "student_uid" => intval($v),
                "status" => self::EXAM_STUDENT_STATUS_PENDING,
                "operator" => OPERATOR,
                "update_time" => time(),
            );
            $ret = $this->daoExamStudent->insertRecords($mapProfile);
            if ($ret == false) {
                $this->daoExam->rollback();
                return false;
            }
        }

        $this->daoExam->commit();
        return true;
    }

    // 定向创建
    public function createReward ($profile) {       
        $this->daoExam->startTransaction();
        $paper = $profile["paper"];
        $userInfo = $profile["userInfo"];

        // 优先创建试卷,
        $daoPaper = new Dao_Paper();
        $paperProfile = array(
            "title" => sprintf("【%s-】%s定向模考", date("m.d"), mt_rand(111, 999),$userInfo["nickname"]),
            "type" => $paper["type"],
            "frequency" => 1,
            "total_score" => $paper["total_score"],
            "total_question" => $paper["total_question"],
            "operator" => OPERATOR,
            "update_time" => time(),
            "create_time" => time(),   
            "ext" => json_encode(array("is_reward" => 1)),
        );
        $ret = $daoPaper->insertRecords($paperProfile);        
        if ($ret ==false) {
            $this->daoExam->rollback();
            return false;
        }

        $pid = $daoPaper->getInsertId();
        $pid = intval($pid);
        if ($pid <= 0) {
            $this->daoExam->rollback();
            return false;            
        }

        // 录题
        $daoPaperQuestion = new Dao_PaperQuestion();
        foreach ($paper["questions"] as $v) {
            $pProfile = array(
                "pid"     => $pid,
                "qid"     => intval($v["qid"]),
                "score"   => empty($v["score"]) ? 0 : $v["score"],
                "update_time"   => time(),
            );
            $ret = $daoPaperQuestion->insertRecords($pProfile);
            if ($ret == false) {
                $this->daoExam->rollback();
                return false;                       
            }
        }      
        
        //  创建考试
        $examProfile = array(
            "indentify" => $profile["indentify"],
            "pid" => $pid,
            "paper_type" => $paper["type"],
            "total_score" => $paper["total_score"],
            "total_question" => $paper["total_question"],
            "start_time" => $profile["start_time"],
            "end_time" => $profile["end_time"],
            "expire_time" => $profile["expire_time"],
            "remark" => $profile["remark"],
            "teacher_uid" => $profile["teacher_uid"],
            "status" => self::EXAM_STATUS_PENDING,
            "operator" => OPERATOR,
            "update_time" => time(),
            "create_time" => time(),
            "ext" => json_encode(array("is_reward" => 1)),
        );
        $ret = $this->daoExam->insertRecords($examProfile);
        if ($ret == false) {
            $this->daoExam->rollback();
            return false;                       
        }    
        $examId = $this->daoExam->getInsertId();
        if ($examId <= 0) {
            $this->daoExam->rollback();
            return false;            
        }
        $examId = intval($examId);         
        
        // 关联考生
        foreach ($profile["student_uids"] as $v) {
            $mapProfile = array(
                "exam_id" => $examId,
                "pid" => $pid,
                "student_uid" => intval($v),
                "status" => self::EXAM_STUDENT_STATUS_PENDING,
                "operator" => OPERATOR,
                "update_time" => time(),
            );
            $ret = $this->daoExamStudent->insertRecords($mapProfile);
            if ($ret == false) {
                $this->daoExam->rollback();
                return false;
            }
        }

        $this->daoExam->commit();
        return true;
    }    

    // 修改
    public function update ($id, $profile) {       
        $this->daoExam->startTransaction();
        $paper = $profile["paper"];
        
        //  创建考试
        $examProfile = array(
            "pid" => $profile["pid"],
            "paper_type" => $paper["type"],
            "total_score" => $paper["total_score"],
            "total_question" => $profile["total_question"],
            "start_time" => $profile["start_time"],
            "end_time" => $profile["end_time"],
            "expire_time" => $profile["expire_time"],
            "remark" => $profile["remark"],
            "teacher_uid" => $profile["teacher_uid"],
            "status" => self::EXAM_STATUS_PENDING,
            "operator" => OPERATOR,
            "update_time" => time(),
            "create_time" => time(),
        );
        $conds = array(
            "id" => $id,
            "status" => self::EXAM_STATUS_PENDING,
        );
        $ret = $this->daoExam->updateByConds($conds, $examProfile);
        if ($ret == false) {
            $this->daoExam->rollback();
            return false;                       
        }

        // 先删再关联考生
        $conds = array(
            "exam_id" => $id,
            "status" => self::EXAM_STATUS_PENDING,
        );
        $ret = $this->daoExamStudent->deleteByConds($conds);
        if ($ret == false) {
            $this->daoExam->rollback();
            return false;                       
        }        
        foreach ($profile["student_uids"] as $v) {
            $mapProfile = array(
                "exam_id" => $id,
                "pid" => $profile["pid"],
                "student_uid" => intval($v),
                "status" => self::EXAM_STUDENT_STATUS_PENDING,
                "operator" => OPERATOR,
                "update_time" => time(),
            );
            $ret = $this->daoExamStudent->insertRecords($mapProfile);
            if ($ret == false) {
                $this->daoExam->rollback();
                return false;
            }
        }

        $this->daoExam->commit();
        return true;
    }  

    // 删除
    public function delete ($id) {       
        $this->daoExam->startTransaction();
        
        // 删考试
        $conds = array(
            "id" => $id,
            "status" => self::EXAM_STATUS_PENDING,
        );
        $ret = $this->daoExam->deleteByConds($conds);
        if ($ret == false) {
            $this->daoExam->rollback();
            return false;                       
        }

        // 先删关联考生
        $conds = array(
            "exam_id" => $id,
            "status" => self::EXAM_STUDENT_STATUS_PENDING,
        );
        $ret = $this->daoExamStudent->deleteByConds($conds);
        if ($ret == false) {
            $this->daoExam->rollback();
            return false;                       
        }
        $this->daoExam->commit();
        return true;
    }      

    // 列表
    public function getListByConds($conds, $field = array(), $indexs = null, $appends = null) {
        $field = empty($field) || !is_array($field) ? $this->daoExam->arrFieldsMap : $field;
        $lists = $this->daoExam->getListByConds($conds, $field, $indexs, $appends);
        if (empty($lists)) {
            return array();
        }
        return $lists;
    }

    // 列表
    public function getStudentListByConds($conds, $field = array(), $indexs = null, $appends = null) {
        $field = empty($field) || !is_array($field) ? $this->daoExamStudent->arrFieldsMap : $field;
        $lists = $this->daoExamStudent->getListByConds($conds, $field, $indexs, $appends);
        if (empty($lists)) {
            return array();
        }
        return $lists;
    }    

    // 列表
    public function getStudentAnswerListByConds($conds, $field = array(), $indexs = null, $appends = null) {
        $field = empty($field) || !is_array($field) ? $this->daoExamAnswer->arrFieldsMap : $field;
        $lists = $this->daoExamAnswer->getListByConds($conds, $field, $indexs, $appends);
        if (empty($lists)) {
            return array();
        }
        return $lists;
    }        

    // 单独一项
    public function getRecordByConds($conds, $field = array(), $indexs = null, $appends = null) {
        $field = empty($field) || !is_array($field) ? $this->daoExam->arrFieldsMap : $field;
        $Record = $this->daoExam->getRecordByConds($conds, $field, $indexs, $appends);
        if (empty($Record)) {
            return array();
        }
        return $Record;
    }

    // 单独一项
    public function getStudentRecordByConds($conds, $field = array(), $indexs = null, $appends = null) {
        $field = empty($field) || !is_array($field) ? $this->daoExamStudent->arrFieldsMap : $field;
        $Record = $this->daoExamStudent->getRecordByConds($conds, $field, $indexs, $appends);
        if (empty($Record)) {
            return array();
        }
        return $Record;
    }   
    
    // 单独一项
    public function getStudentAnswerRecordByConds($conds, $field = array(), $indexs = null, $appends = null) {
        $field = empty($field) || !is_array($field) ? $this->daoExamAnswer->arrFieldsMap : $field;
        $Record = $this->daoExamAnswer->getRecordByConds($conds, $field, $indexs, $appends);
        if (empty($Record)) {
            return array();
        }
        return $Record;
    }       

    public function getTotalByConds($conds) {
        return  $this->daoExam->getCntByConds($conds);
    }

    public function getStudentTotalByConds($conds) {
        return  $this->daoExamStudent->getCntByConds($conds);
    }    

    public function getStudentAnswerTotalByConds($conds) {
        return  $this->daoExamAnswer->getCntByConds($conds);
    }        


    /****** 考生的 *********/
    
    // 单人开始
    public function studentStart ($examId, $studentUid, $isStudentPending = true, $isExamPending = true) {
        $this->daoExamStudent->startTransaction();
        // 跟新个人
        if ($isStudentPending) {
            $conds = array(
                "exam_id" => $examId,
                "student_uid" => $studentUid,
            );
            $profile = array(
                "start_time" => time(),
                "status" => self::EXAM_STUDENT_STATUS_ONGOING,
                "update_time" => time(),
            );
            $ret = $this->daoExamStudent->updateByConds($conds, $profile);
            if ($ret == false) {
                $this->daoExamStudent->rollback();
                return false;            
            }
        }

        // 如果考试未开启, 让考试进行中
        if ($isExamPending) {
            $conds = array(
                "id" => $examId,
            );
            $profile = array(
                "status" => self::EXAM_STUDENT_STATUS_ONGOING,
                "update_time" => time(),
            );
            $ret = $this->daoExam->updateByConds($conds, $profile);
            if ($ret == false) {
                $this->daoExamStudent->rollback();
                return false;            
            }            
        }
        $this->daoExamStudent->commit();
        return true;        
    }   

    // 考生答案记录
    public function saveAnswer($examId, $qid, $uid, $profile, $spendTime1) {
        $this->daoExamAnswer->startTransaction();
        //先删后加
        $conds = array(
            "exam_id" => $examId,
            "qid" => $qid,
            "student_uid" => $uid,
        );
        $ret = $this->daoExamAnswer->deleteByConds($conds);
        if ($ret == false) {
            $this->daoExamAnswer->rollback();
            return false;
        }
        
        foreach ($profile as $item) {
            $ret = $this->daoExamAnswer->insertRecords($item);
            if ($ret == false) {
                $this->daoExamAnswer->rollback();
                return false;
            }
        }

        // 更新时间
        $conds = array(
            "exam_id" => $examId,
            "student_uid" => $uid,
        );
        $eprofile = array(
            "spend_time" => $spendTime1,
            "update_time" => time(),
        );
        $ret = $this->daoExamStudent->updateByConds($conds, $eprofile);
        if ($ret == false) {
            $this->daoExamAnswer->rollback();
            return false;
        }
        $this->daoExamAnswer->commit();
        return true;
    }    
    
    // 单人踢下线
    public function studentTerminatedEnd ($examId, $studentUid) {       
        $conds = array(
            "exam_id" => $examId,
            "student_uid" => $studentUid,
            sprintf("status in (%s)", implode(",", [
                self::EXAM_STUDENT_STATUS_ONGOING, 
                self::EXAM_STUDENT_STATUS_PENDING
            ]))
        );
        $profile = array(
            "status" => self::EXAM_STUDENT_STATUS_TERMINATED,
            "operator" => OPERATOR,
            "update_time" => time(),
            "ext" => json_encode(array(
                "last_status" => self::EXAM_STUDENT_STATUS_TERMINATED,
                "operator" => OPERATOR,
            )),
        );
        return $this->daoExamStudent->updateByConds($conds, $profile);
    }

    // 单人交卷
    public function studentSubmit ($examId, $studentUid, $spendTime) {       
        $conds = array(
            "exam_id" => $examId,
            "student_uid" => $studentUid,
        );
        $profile = array(
            "status" => self::EXAM_STUDENT_STATUS_COMPLETE,
            "spend_time" => $spendTime,
            "end_time" => time(),
            "update_time" => time(),
            "ext" => json_encode(array(
                "last_status" => self::EXAM_STUDENT_STATUS_COMPLETE,
            )),            
        );
        return $this->daoExamStudent->updateByConds($conds, $profile);
    }

    // 考试结束
    public function examEnd ($examId, $status, $studentUids) {   
        $this->daoExam->startTransaction();
        
        if ($status == self::EXAM_STATUS_TERMINATED) {
            $conds = array(
                "exam_id" => $examId,
                sprintf("student_uid in (%s)", implode(",", $studentUids)),
                "status in (1,5)",
            );
            $profile = array(
                "status" => self::EXAM_STUDENT_STATUS_TERMINATED,
                "operator" => OPERATOR,
                "update_time" => time(),
                "ext" => json_encode(array(
                    "last_status" => self::EXAM_STUDENT_STATUS_TERMINATED,
                    "operator" => OPERATOR,
                )),                
            );
            $ret = $this->daoExamStudent->updateByConds($conds, $profile);
            if ($ret == false) {
                $this->daoExam->rollback();
                return false;
            }
        }
        $conds = array(
            "id" => $examId,
            "status in (1,5)"
        );
        $profile = array(
            "status" => $status,
            "operator" => OPERATOR,
            "update_time" => time(),
        );        

        $ret = $this->daoExam->updateByConds($conds, $profile);
        if ($ret == false) {
            $this->daoExam->rollback();
            return false;
        }
        $this->daoExam->commit();
        return true;        
    }   

    // 老师批改
    public function reviewSave ($examId, $studentUid, $qid, $profile) {
        $conds = array(
            "exam_id" => $examId,
            "student_uid" => $studentUid,
            "qid" => $qid,
        );
        $uprofile = array(
            "review_content" => $profile["review_content"],
            "score" => $profile["review_score"],
            "update_time" => time(),
        );
        if (!empty($profile["review_ai"])) {
            $uprofile["ext"] = json_encode(array("review_ai" => $profile["review_ai"]));
        }
        $saveStatus= $this->daoExamAnswer->updateByConds($conds, $uprofile);
        if ($saveStatus == false) {
            return false;
        }

        // 更新状态 (失败等待下次更新)
        if (isset($profile["studentExam"]["status"]) && 
            $profile["studentExam"]["status"] != self::EXAM_STUDENT_STATUS_REVIEWING) {
            $this->daoExamStudent->updateByConds(array(
                "exam_id" => $examId,
                "student_uid" => $studentUid,                
            ), array(
                "update_time" => time(),
                "status" => self::EXAM_STUDENT_STATUS_REVIEWING,
                "operator" => OPERATOR,
            ));
        }
        return $saveStatus;
    } 

    // 批改结束
    public function reviewFinish ($examId, $studentUid, $score) {       
        $conds = array(
            "exam_id" => $examId,
            "student_uid" => $studentUid,
        );
        $profile = array(
            "status" => self::EXAM_STUDENT_STATUS_FINISHED,
            "score" => $score,
            "operator" => OPERATOR,
            "update_time" => time(),
        );
        return $this->daoExamStudent->updateByConds($conds, $profile);
    }         


    /************获取考题相关****************/
    /**
     *  get student answer detail (mis review用)
     *  questions  (包含答案和作答全部信息)
     *  metas
     *  studentScore
     */ 
    public function getStudentAnswerDetailForReview ($examId, $pid, $studentUid) {
        $studetnAnswer = $this->getAnswerByExamId($examId, $studentUid);
        if (empty($studetnAnswer)) {
            throw new Zy_Core_Exception(100001, "未作答");
        }
        $qids = Zy_Helper_Utils::arrayInt($studetnAnswer, "qid");

        // get paper question
        $daoPaperQuestion = new Dao_PaperQuestion();
        $paperQuestion = $daoPaperQuestion->getListByConds(array(
            "pid" => $pid, 
            sprintf("qid in (%s)", implode(",",$qids))
        ), array("qid", "score"));
        if (empty($paperQuestion)) {
            throw new Zy_Core_Exception(100002, "试题映射为空");
        }

        // 拉取试题信息
        $serviceQuestion = new Service_Data_Question();
        try {
            $ret = $serviceQuestion->getQuestionDetails($qids, $paperQuestion, $studetnAnswer);
        } catch (Exception $e) {
            throw new Zy_Core_Exception(100002, "试题详细为空");
        }

        return $ret;
    }

    /**
     *  get student answer detail (mock用)
     *  questions  (包含答案和作答全部信息)
     *  metas
     *  studentScore
     */ 
    public function getStudentAnswerDetailForMock ($examId, $pid, $studentUid) {
        $ret = $this->getStudentAnswerDetailForReview($examId, $pid, $studentUid);
        // 结构化去掉一些东西
        foreach ($ret["questions"] as &$question) {
            // 学员批改去掉一些
            if (!empty($question["studentAnswer"])) {
                foreach ($question["studentAnswer"] as &$answer) {
                    if (in_array($question["type"], [
                        Service_Data_Question::QUESTION_TYPE_SIMPLEWRITE,
                        Service_Data_Question::QUESTION_TYPE_WRITE,
                    ])) {
                        $answer["answerContent"] = empty($answer["answerContent"]) ? "" : 
                            implode("<br/>", explode("\n", $answer["answerContent"]));
                    }
                    unset($answer["reviewAI"]);
                }
            }
        }
        return $ret ;
    }    

    /**
     *  get student answer detail (学员考试专用 - 常规)
     *  questions  (包含答案和作答全部信息)
     *  metas
     *  studentScore
     */ 
    public function getStudentAnswerDetailForNormalExam ($examId, $pid, $studentUid) {
        // 试题映射
        $servicePaper = new Service_Data_Paper();
        $paperQuestion = $servicePaper->getPaperQuestions($pid);
        if (empty($paperQuestion)) {
            throw new Zy_Core_Exception(100001, "试题映射不存在");
        }
        $qids = Zy_Helper_Utils::arrayInt($paperQuestion, "qid");

        // 学生作答
        $studetnAnswer = $this->getAnswerByExamId($examId, $studentUid);

        // 拉取试题信息
        $serviceQuestion = new Service_Data_Question();
        try {
            $ret = $serviceQuestion->getQuestionDetails($qids, $paperQuestion, $studetnAnswer);
        } catch (Exception $e) {
            throw new Zy_Core_Exception(100002, "试题详细为空");
        }
        unset($ret["studentScore"]);
        
        // 结构化去掉一些东西
        foreach ($ret["questions"] as &$question) {
            unset($question["explan"]);
            unset($question["score"]);
            // 试题答案
            if (!empty($question["questionAnswer"])) {
                foreach ($question["questionAnswer"] as &$answer) {
                    unset($answer["isCorrect"]);
                    if ($question["type"] == Service_Data_Question::QUESTION_TYPE_FILL) {
                        $answer["answerContent"] = "";
                    }
                }
            }
            // 学员答案
            if (!empty($question["studentAnswer"])) {
                foreach ($question["studentAnswer"] as &$answer) {
                    unset($answer["isCorrect"]);
                    unset($answer["reviewAI"]);
                    unset($answer["reviewContent"]);
                    unset($answer["score"]);

                    if (in_array($question["type"], [
                        Service_Data_Question::QUESTION_TYPE_SIMPLEWRITE,
                        Service_Data_Question::QUESTION_TYPE_WRITE,
                    ])) {
                        $answer["answerContent"] = empty($answer["answerContent"]) ? "" : 
                            implode("<br/>", explode("\n", $answer["answerContent"]));
                    }                    
                }
            }            
        }


        return $ret ;
    }   
    
    /**
     *  get student answer detail (学员考试专用 - 评估)
     *  questions  (包含答案和作答全部信息)
     *  metas
     *  studentScore
     */ 
    public function getStudentAnswerDetailForAssessExam ($examId, $pid, $studentUid) {
        // 试题映射
        $servicePaper = new Service_Data_Paper();
        $paperQuestion = $servicePaper->getPaperQuestions($pid);
        if (empty($paperQuestion)) {
            throw new Zy_Core_Exception(100001, "试题映射异常");
        }
        $qids = Zy_Helper_Utils::arrayInt($paperQuestion, "qid");

        // 先简单查询映射难度在分组
        $daoQuestion = new Dao_Question();
        $simpleQuestions = $daoQuestion->getListByConds(array(
            sprintf("qid in (%s)", implode(",", $qids)),
        ), array("qid", "level", "parent_id"));
        if (empty($simpleQuestions)) {
            throw new Zy_Core_Exception(100001, "试题映射异常!");
        }

        // 学生作答
        $studetnAnswer = $this->getAnswerByExamId($examId, $studentUid);   
        $studetnAnswerQids = Zy_Helper_Utils::arrayInt($studetnAnswer, "qid");

        
        // 做完的
        $finishQuestionLevelMap = array();
        // 全量的
        $paperQuestionLevelMap = array();
        // l1, l2, l3, l4, l5 ....
        foreach ($simpleQuestions as $v) {
            // 在完成中, 就不往全部里写了, 后续直接从去哪里拿填充
            if (!isset($finishQuestionLevelMap["l".$v["level"]])) {
                $finishQuestionLevelMap["l".$v["level"]] = array();
            }
            if (!isset($paperQuestionLevelMap["l".$v["level"]])) {
                $paperQuestionLevelMap["l".$v["level"]] = array();
            }            
            if (in_array($v["qid"], $studetnAnswerQids)) {
                $finishQuestionLevelMap["l".$v["level"]][] = $v['qid'];
            } else {
                $paperQuestionLevelMap["l".$v["level"]][] = $v['qid']; 
            }
        }

        // 数量不够填充,&& 取出所有qid
        foreach ($finishQuestionLevelMap as $level => $qidArr) {
            if (count($qidArr) < self::SIGNLE_LEVEL_CNT && !empty($paperQuestionLevelMap[$level])) {
                shuffle($paperQuestionLevelMap[$level]);
                $qidArr = array_merge($qidArr, 
                    array_slice($paperQuestionLevelMap[$level], 0 , self::SIGNLE_LEVEL_CNT - count($qidArr)));
                $finishQuestionLevelMap[$level] = $qidArr;
            }
        }

        // 根据难度排序
        ksort($finishQuestionLevelMap);
        $outputQids = array();
        foreach ($finishQuestionLevelMap as $level => $qidArr) {
            $outputQids = array_merge($outputQids, $qidArr);
        }        
        if (empty($outputQids)) {
            throw new Zy_Core_Exception(100002, "试题策略异常");
        }

        // 拉取试题信息
        $serviceQuestion = new Service_Data_Question();
        try {
            $ret = $serviceQuestion->getQuestionDetails($outputQids, array(), $studetnAnswer);
        } catch (Exception $e) {
            throw new Zy_Core_Exception(100002, "试题详情异常");
        }
        unset($ret["studentScore"]);
        
        // 结构化去掉一些东西
        foreach ($ret["questions"] as &$question) {
            unset($question["explan"]);
            unset($question["score"]);
            // 试题答案
            if (!empty($question["questionAnswer"])) {
                foreach ($question["questionAnswer"] as &$answer) {
                    unset($answer["isCorrect"]);
                    if ($question["type"] == Service_Data_Question::QUESTION_TYPE_FILL) {
                        $answer["answerContent"] = "";
                    }
                }
            }
            // 学员答案
            if (!empty($question["studentAnswer"])) {
                foreach ($question["studentAnswer"] as &$answer) {
                    unset($answer["isCorrect"]);
                    unset($answer["reviewAI"]);
                    unset($answer["reviewContent"]);
                    unset($answer["score"]);

                    if (in_array($question["type"], [
                        Service_Data_Question::QUESTION_TYPE_SIMPLEWRITE,
                        Service_Data_Question::QUESTION_TYPE_WRITE,
                    ])) {
                        $answer["answerContent"] = empty($answer["answerContent"]) ? "" : 
                            implode("<br/>", explode("\n", $answer["answerContent"]));
                    }                    
                }
            }            
        }
        return $ret ;
    }    
    
    /**
     *  get getNextLevel
     *  0 是当前level
     *  1-5
     */ 
    public function getNextLevel ($examId, $studentUid, $studentExam) {
        // 学生作答的正确情况统计
        $studetnAnswer = $this->getAnswerByExamId($examId, $studentUid);  
        $studentExam = $this->getMuiltExamAnswerDetail(array($studentExam), $studetnAnswer);
        $studentExam = array_column($studentExam, null, "exam_id");
        $studentExam = $studentExam[$examId];
        if (empty($studentExam["studentAnswerRet"])) {
            return 0;
        }
        $studentAnswerRet = $studentExam["studentAnswerRet"];
        $qids = Zy_Helper_Utils::arrayInt(array_keys($studentAnswerRet));

        // 根据学员level进行统计
        $serviceQuestion = new Service_Data_Question();
        $questions = $serviceQuestion->getQuestionByIds($qids, true);
        if (empty($questions)) { // 查询失败当前级别
            return 0;
        }
        $questions = array_column($questions, null, 'qid');
        foreach ($studentAnswerRet as $qid => &$item) {
            $item["level"] = "l" . $questions[$qid]["level"] ;
        }

        $baseLevel = array();
        foreach (Service_Data_Question::QUESTION_LEVEL_MAP as $v) {
            $baseLevel["l" . $v] = array("total" => self::SIGNLE_LEVEL_CNT, "correct" => 0, "level_num" => $v);
        }
        foreach ($studentAnswerRet as $v) {
            if (isset($baseLevel[$v["level"]]) && $v["is_correct"] == 1) {
                $baseLevel[$v['level']]["correct"]++;
            }
            $baseLevel[$v['level']]["rate"] = sprintf("%.2f", $baseLevel[$v['level']]["correct"] / $baseLevel[$v['level']]["total"]);
        }

        $nextLevel = 0;
        // 最小level
        $selectedLevel = min(Zy_Helper_Utils::arrayInt($baseLevel, "level_num"));
        // 最大level
        $maxLevel = max(Zy_Helper_Utils::arrayInt($baseLevel, "level_num"));

        ksort($baseLevel);
        
        foreach ($baseLevel as $level => $item) {
            // 如果当前level正确率大于75%，则考虑升级
            if ($item["rate"] > 0.75) {
                // 检查下一级是否存在，如果不存在则创建下一级
                $nextLevel = $item["level_num"] + 1;
                
                // 如果下一级比当前选中的level高，则更新selectedLevel
                if ($nextLevel > $selectedLevel) {
                    $selectedLevel = $nextLevel;
                }
                
                // 确保不超过最高level+1
                if ($selectedLevel > $maxLevel) {
                    $selectedLevel = $maxLevel;
                }
            } else {
                // 如果正确率不达标，则停留在当前level
                // 但要确保selectedLevel不小于当前level
                if ($level > $selectedLevel) {
                    $selectedLevel = $level;
                }
                break; // 遇到第一个不达标的level就停止
            }
        }
        return $selectedLevel;
    }
}