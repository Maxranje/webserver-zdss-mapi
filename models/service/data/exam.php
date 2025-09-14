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

    // 学员模考状态, 1:待开始, 5, 进行中. 2 完成, 4:强制结束
    const EXAM_STUDENT_STATUS_PENDING       = 1;
    const EXAM_STUDENT_STATUS_ONGOING       = 5;
    const EXAM_STUDENT_STATUS_COMPLETE      = 2;
    const EXAM_STUDENT_STATUS_TERMINATED    = 4;

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

    public function getExamByPaperId ($pid) {
        $arrConds = array(
            sprintf("pid = %d", $pid)
        );

        $data = $this->daoExam->getListByConds($arrConds, $this->daoExam->arrFieldsMap);
        if (empty($data)) {
            return array();
        }

        return $data;
    }    

    // 根据student_uid获取模考记录
    public function getExamByStudentUids ($uids, $needInfo = false) {
        $arrConds = array(
            sprintf("student_uid in (%s)", implode(",", $uids))
        );

        $data = $this->daoExamStudent->getListByConds($arrConds, $this->daoExamStudent->arrFieldsMap);
        if (empty($data)) {
            return array();
        }

        $examInfos = array();
        if ($needInfo) {
            $examIds = Zy_Helper_Utils::arrayInt($data, "exam_id");
            $examInfos = $this->getExamByIds($examIds);
            $examInfos = array_column($examInfos, null, "id");
        }

        $ret = array();
        foreach ($data as $v) {
            if (!isset($ret[$v["student_uid"]])) {
                $ret[$v["student_uid"]] = array();
            }
            $item = $v;
            if ($needInfo && !empty($examInfos[$v["exam_id"]]['indentify'])) {
                $item["exam"] = $examInfos[$v["exam_id"]];
            }
            $ret[$v["student_uid"]][] = $item;
        }

        return $ret;
    }

    // 根据student_uid获取模考记录
    public function getStudentByExamIds ($examIds, $needInfo = false) {
        $arrConds = array(
            sprintf("exam_id in (%s)", implode(",", $examIds))
        );

        $data = $this->daoExamStudent->getListByConds($arrConds, $this->daoExamStudent->arrFieldsMap);
        if (empty($data)) {
            return array();
        }

        $studentInfos = array();
        if ($needInfo) {
            $serviceUser = new Service_Data_Profile();
            $studentUids = Zy_Helper_Utils::arrayInt($data, "student_uid");
            $studentInfos = $serviceUser->getUserInfoByUids($studentUids);
            $studentInfos = array_column($studentInfos, null, "uid");
        }

        $ret = array();
        foreach ($data as $v) {
            if (!isset($ret[$v["exam_id"]])) {
                $ret[$v["exam_id"]] = array();
            }
            $item = $v;
            if ($needInfo && !empty($studentInfos[$v["student_uid"]]['nickname'])) {
                $item["student"] = $studentInfos[$v["student_uid"]];
            }
            $ret[$v["exam_id"]][] = $item;
        }

        return $ret;
    }

    // 根据student_uid获取模考作答记录
    public function getAnswerByExamId ($examId, $simple = false, $studentUid = 0) {
        $arrConds = array(
            sprintf("exam_id = %d", $examId),
        );
        if ($studentUid > 0) {
            $arrConds[] = sprintf("student_uid = %d", $studentUid);
        }

        $fileds = $simple ? $this->daoExamAnswer->simpleFieldsMap : $this->daoExamAnswer->arrFieldsMap;
        $data = $this->daoExamAnswer->getListByConds($arrConds, $fileds);
        if (empty($data)) {
            return array();
        }

        $ret = array();
        foreach ($data as $v) {
            if (!isset($ret[$v["student_uid"]])) {
                $ret[$v["student_uid"]] = array();
            }
            $ret[$v["student_uid"]][] = $v;
        }

        return $ret;
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
        // 关联学员
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

    // 修改
    public function update ($id, $profile) {       
        $this->daoExam->startTransaction();
        $paper = $profile["paper"];
        
        //  创建考试
        $examProfile = array(
            "pid" => $profile["pid"],
            "paper_type" => $paper["type"],
            "total_score" => $paper["total_score"],
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

        // 先删再关联学员
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

    // 单人结束
    public function studentEnd ($examId, $studentUid) {       
        $conds = array(
            "exam_id" => $examId,
            "student_uid" => $studentUid,
        );
        $profile = array(
            "status" => self::EXAM_STUDENT_STATUS_TERMINATED,
            "operator" => OPERATOR,
            "update_time" => time(),
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
                "status in (1,5)"
            );
            $profile = array(
                "status" => self::EXAM_STUDENT_STATUS_TERMINATED,
                "operator" => OPERATOR,
                "update_time" => time(),
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

    // 列表
    public function getListByConds($conds, $field = array(), $indexs = null, $appends = null) {
        $field = empty($field) || !is_array($field) ? $this->daoExam->arrFieldsMap : $field;
        $lists = $this->daoExam->getListByConds($conds, $field, $indexs, $appends);
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

    public function getTotalByConds($conds) {
        return  $this->daoExam->getCntByConds($conds);
    }


    /***************** 功能接口  ***********************/

    public function getExamProccess($examId, $pid, $studentUid = 0) {
        $servicePaper = new Service_Data_Paper();
        $serviceQuestion = new Service_Data_Question();

        // 获取试题
        $questions = $servicePaper->getPaperQuestionIds($pid);
        $questions = empty($questions[$pid]) ? array() : $questions[$pid];
        if (empty($questions)) {
            throw new Zy_Core_Exception(405, "获取试题信息失败");
        }
    }
}