<?php
// 留学计划

class Service_Data_Aporderpackage {

    private $daoAporderpackage ;

    // 订单状态
    const APORDER_STATUS_ADDDUR_PEND   = 4;  // 加课时中
    const APORDER_STATUS_TRANS_REFUES  = 33; // 结转审核失败
    const APORDER_STATUS_TRANS_PEND    = 32; // 结转审核中
    const APORDER_STATUS_TRANS         = 3;  // 结转
    const APORDER_STATUS_DONE_REFUES   = 23; // 完结拒绝
    const APORDER_STATUS_DONE_PEND     = 22; // 完结审核中
    const APORDER_STATUS_DONE          = 2;  // 完结 
    const APORDER_STATUS_ABLE_REFUES   = 13; // 服务拒绝
    const APORDER_STATUS_ABLE_PEND     = 12; // 服务审核中
    const APORDER_STATUS_ABLE          = 1;  // 服务有效

    const APORDER_STATUS_ABLE_MAP = [  // 有效的
        self::APORDER_STATUS_ABLE,
        self::APORDER_STATUS_ADDDUR_PEND,
    ];
    
    public function __construct() {
        $this->daoAporderpackage = new Dao_Aporderpackage () ;
    }

    // 根据ID获取服务包
    public function getAbroadpackageById ($id){
        $arrConds = array(
            'id'  => $id,
        );

        $arrFields = $this->daoAporderpackage->arrFieldsMap;

        $record = $this->daoAporderpackage->getRecordByConds($arrConds, $arrFields);
        if (empty($record)) {
            return array();
        }

        return $record;
    }

    // 根据ID获取服务包(带confirm)
    public function getAbroadpackageByIdWithConfirm ($id){
        $arrConds = array(
            'id'  => $id,
        );

        $arrFields = $this->daoAporderpackage->arrFieldsMap;

        $record = $this->daoAporderpackage->getRecordByConds($arrConds, $arrFields);
        if (empty($record)) {
            return array();
        }

        return $record;
    }    

    // 根据IDS获取服务包
    public function getAbroadpackageByIds ($ids){
        $arrConds = array(
            sprintf("id in (%s)", implode(",", $ids)),
        );

        $arrFields = $this->daoAporderpackage->arrFieldsMap;

        $lists = $this->daoAporderpackage->getListByConds($arrConds, $arrFields);
        if (empty($lists)) {
            return array();
        }

        return $lists;
    } 

    // 根据IDS获取服务数
    public function getApackageCountByUids ($uids){
        $result = array();
        foreach ($uids as $uid) {
            if (!isset($result[$uid])) {
                $result[$uid] = 0;
            }
        }
        $arrConds = array(
            sprintf("uid in (%s)", implode(",", $uids)),
            sprintf("state in (%s)" , implode(",",[
                self::APORDER_STATUS_ABLE,
                self::APORDER_STATUS_DONE,
                self::APORDER_STATUS_TRANS,
            ]))
        );

        $arrFields = array("uid, count(id) as count");

        $appends = array(
            "group by uid",
        );

        $data = $this->daoAporderpackage->getListByConds($arrConds, $arrFields, null, $appends);
        if (empty($data)) {
            return $result;
        }

        foreach ($data as $v) {
            $result[$v["uid"]] = intval($v["count"]);
        }

        return $result;
    }    
    
    // 根据uid获取服务
    public function getApackagesByUid ($uid){
        $arrConds = array(
            sprintf("uid = %d", intval($uid)),
            sprintf("state in (%s)" , implode(",",[
                self::APORDER_STATUS_ABLE,
                self::APORDER_STATUS_DONE,
                self::APORDER_STATUS_TRANS,
                self::APORDER_STATUS_ADDDUR_PEND,
            ]))
        );
        $data = $this->daoAporderpackage->getListByConds($arrConds, 
            $this->daoAporderpackage->arrFieldsMap);
        if (empty($data)) {
            return array();
        }

        return $data;
    }      

    // 预创建服务包, 需要过工单
    public function create ($profile) {

        $confirm = empty($profile["confirm"]) ? array() : $profile["confirm"];
        unset($profile["confirm"]);

        $this->daoAporderpackage->startTransaction();
        $ret = $this->daoAporderpackage->insertRecords($profile);
        if ($ret == false) {
            $this->daoAporderpackage->rollback();
            return false;
        }

        $apackageId = $this->daoAporderpackage->getInsertId();
        if (intval($apackageId) <= 0) {
            $this->daoAporderpackage->rollback();
            return false;
        }

        // confirm 配置
        if (!empty($confirm['content'])) {
            $daoConfirm = new Dao_ApackageConfirm();
            $confirmProfile = array(
                "abroadplan_id" => $profile["abroadplan_id"],
                "apackage_id" => $apackageId,
                "content" => json_encode($confirm["content"]),
                "operator" => OPERATOR,
                "update_time" => time(),
                "create_time" => time(),
            );
            $ret = $daoConfirm->insertRecords($confirmProfile);
            if ($ret == false) {
                $this->daoAporderpackage->rollback();
                return false;
            }
        }

        // 更新用户信息, 提前从账户扣钱
        $daoUser = new Dao_User();
        $uprofile = array(
            sprintf("balance=balance-%d", intval($profile["price"])),
            'update_time' => time(),
        );
        $ret = $daoUser->updateByConds(array("uid"=> $profile['uid']), $uprofile);
        if ($ret == false) {
            $this->daoAporderpackage->rollback();
            return false;
        }

        // 增加审批
        $daoReview = new Dao_Review();
        $reviewProfile = array(
            "type"          => Service_Data_Review::REVIEW_TYPE_APACKAGE_CREATE,
            "state"         => Service_Data_Review::REVIEW_ING,
            "uid"           => $profile['uid'], 
            "sop_uid"       => OPERATOR,
            "work_id"       => $apackageId,
            "update_time"   => time(),
            "create_time"   => time(),
        );
        $ret = $daoReview->insertRecords($reviewProfile);
        if ($ret == false) {
            $this->daoAporderpackage->rollback();
            return false;
        }
        $this->daoAporderpackage->commit();
        return true;
    }

    // 删除服务包
    public function delete ($id, $apackageInfo, $abroadplanInfo) {
        $this->daoAporderpackage->startTransaction();
        $daoChange = new Dao_Orderchange();
        $daoConfirm = new Dao_ApackageConfirm();
        $changePorfile = array(
            "order_id"          => 0, 
            "student_uid"       => intval($apackageInfo['uid']), 
            "type"              => Service_Data_Orderchange::CHANGE_APORDER_PACKAGE_DELETE,
            "balance"           => 0,
            "duration"          => 0,
            "order_info"        => json_encode(array(
                "abroadplan_name" => $abroadplanInfo["name"],
            )),
            "operator"          => OPERATOR, 
            'update_time'       => time(),
            'create_time'       => time(),
        );

        $ret = $daoChange->insertRecords($changePorfile);
        if ($ret == false) {
            $this->daoAporderpackage->rollback();
            return false;
        }
        $ret = $daoConfirm->deleteByConds(array('apackage_id' => $id));
        if ($ret == false) {
            $this->daoAporderpackage->rollback();
            return false;
        }
        $ret = $this->daoAporderpackage->deleteByConds(array('id' => $id));
        if ($ret == false) {
            $this->daoAporderpackage->rollback();
            return false;
        }
        $this->daoAporderpackage->commit();
        return true;
    } 

    // 完结留学服务包
    public function done ($apackageId, $studentUid, $profile) {
        $this->daoAporderpackage->startTransaction();

        $ret = $this->daoAporderpackage->updateByConds(array('id' => $apackageId), $profile);
        if ($ret == false) {
            $this->daoAporderpackage->rollback();
            return false;
        }
        // 增加审批
        $daoReview = new Dao_Review();
        $reviewProfile = array(
            "type"          => Service_Data_Review::REVIEW_TYPE_APACKAGE_DONE,
            "state"         => Service_Data_Review::REVIEW_ING,
            "uid"           => intval($studentUid), 
            "sop_uid"       => OPERATOR,
            "work_id"       => intval($apackageId),
            "update_time"   => time(),
            "create_time"   => time(),
        );
        $ret = $daoReview->insertRecords($reviewProfile);
        if ($ret == false) {
            $this->daoAporderpackage->rollback();
            return false;
        }        
        $this->daoAporderpackage->commit();
        return true;        
    }

    // 计划订单增加课时, 提交审批
    public function apackageDurationAdd ($profile) {
        $this->daoAporderpackage->startTransaction();

        $conds = array(
            "id" => intval($profile["apackage_id"]),
        );
        $uprofile = array(
            "state" => self::APORDER_STATUS_ADDDUR_PEND,
            "update_time" => time(),
        );
        $ret = $this->daoAporderpackage->updateByConds($conds, $uprofile);
        if ($ret == false) {
            $this->daoAporderpackage->rollback();
            return false;
        }
        // 增加审批
        $daoReview = new Dao_Review();
        $reviewProfile = array(
            "type"          => Service_Data_Review::REVIEW_TYPE_APACKAGE_DURATION,
            "state"         => Service_Data_Review::REVIEW_ING,
            "uid"           => $profile['student_uid'], 
            "sop_uid"       => OPERATOR,
            "work_id"       => intval($profile["apackage_id"]),
            "update_time"   => time(),
            "create_time"   => time(),
            "ext" => json_encode(array(
                "remark" => $profile["remark"],
                "schedule_nums" => $profile["schedule_nums"],
            )),
        );
        $ret = $daoReview->insertRecords($reviewProfile);
        if ($ret == false) {
            $this->daoAporderpackage->rollback();
            return false;
        }
        $this->daoAporderpackage->commit();
        return true;
    }

    // 结转新服务
    public function transferNew ($profile) {

        $confirm = empty($profile["confirm"]) ? array() : $profile["confirm"];
        unset($profile["confirm"]);

        $originApackage = $profile["origin_apackage"];
        unset($profile["origin_apackage"]);

        $transferScheduleNums = $profile["transfer_schedule_nums"];
        unset($profile["transfer_schedule_nums"]);          

        $this->daoAporderpackage->startTransaction();

        $ret = $this->daoAporderpackage->insertRecords($profile);
        if ($ret == false) {
            $this->daoAporderpackage->rollback();
            return false;
        }

        $apackageId = $this->daoAporderpackage->getInsertId();
        if (intval($apackageId) <= 0) {
            $this->daoAporderpackage->rollback();
            return false;
        }

        // confirm 配置
        if (!empty($confirm['content'])) {
            $daoConfirm = new Dao_ApackageConfirm();
            $confirmProfile = array(
                "abroadplan_id" => $profile["abroadplan_id"],
                "apackage_id" => $apackageId,
                "content" => json_encode($confirm["content"]),
                "operator" => OPERATOR,
                "update_time" => time(),
                "create_time" => time(),
            );
            $ret = $daoConfirm->insertRecords($confirmProfile);
            if ($ret == false) {
                $this->daoAporderpackage->rollback();
                return false;
            }
        }

        // origin update ext_info
        $originApackageExt = json_decode($originApackage["ext"], true);
        $originApackageExt["transfer_id"] = intval($apackageId);
        $conds = array(
            "id" => intval($originApackage["id"])
        );
        $originApackageProfile = array(
            "state" => self::APORDER_STATUS_TRANS_PEND,
            "ext" => json_encode($originApackageExt),
            "update_time" => time(),
        );
        $ret = $this->daoAporderpackage->updateByConds($conds, $originApackageProfile);
        if ($ret == false) {
            $this->daoAporderpackage->rollback();
            return false;
        }

        // 更新用户信息, 提前从账户扣钱
        if (isset($profile['price']) && $profile['price'] > 0) {
            $daoUser = new Dao_User();
            $uprofile = array(
                sprintf("balance=balance-%d", intval($profile["price"])),
                'update_time' => time(),
            );
            $ret = $daoUser->updateByConds(array("uid"=> $profile['uid']), $uprofile);
            if ($ret == false) {
                $this->daoAporderpackage->rollback();
                return false;
            }
        }

        // 增加审批
        $daoReview = new Dao_Review();
        $reviewProfile = array(
            "type"          => Service_Data_Review::REVIEW_TYPE_APACKAGE_TRANSFER,
            "state"         => Service_Data_Review::REVIEW_ING,
            "uid"           => $profile['uid'], 
            "sop_uid"       => OPERATOR,
            "work_id"       => $apackageId,
            "update_time"   => time(),
            "create_time"   => time(),
            "ext" => json_encode(array(
                "transfer_type" => 1,
                "origin_apackage_id" => $originApackage["id"],
                "origin_abroadplan_id" => $originApackage["abroadplan_id"],
                "distin_apackage_id" => $apackageId,
                "distin_abroadplan_id" => $profile["abroadplan_id"],
                "transfer_schedule_nums" => $transferScheduleNums > 0 ? $transferScheduleNums: 0,
                "transfer_remark" => $profile['remark'],
            )),
        );
        $ret = $daoReview->insertRecords($reviewProfile);
        if ($ret == false) {
            $this->daoAporderpackage->rollback();
            return false;
        }
        $this->daoAporderpackage->commit();
        return true;
    }    

    // 结转目标服务
    public function transferHas ($profile) {

        $originApackage = $profile["origin_apackage"];
        unset($profile["origin_apackage"]);

        $distinApackage = $profile["distin_apackage"];
        unset($profile["distin_apackage"]);        

        $transferScheduleNums = $profile["transfer_schedule_nums"];
        unset($profile["transfer_schedule_nums"]);          

        $this->daoAporderpackage->startTransaction();

        // origin update ext_info
        $originApackageExt = json_decode($originApackage["ext"], true);
        $originApackageExt["transfer_id"] = intval($distinApackage["id"]);
        $conds = array(
            "id" => intval($originApackage["id"])
        );
        $originApackageProfile = array(
            "state" => self::APORDER_STATUS_TRANS_PEND,
            "update_time" => time(),
            "ext" => json_encode($originApackageExt),
        );
        $ret = $this->daoAporderpackage->updateByConds($conds, $originApackageProfile);
        if ($ret == false) {
            $this->daoAporderpackage->rollback();
            return false;
        }

        // 增加审批
        $daoReview = new Dao_Review();
        $reviewProfile = array(
            "type"          => Service_Data_Review::REVIEW_TYPE_APACKAGE_TRANSFER,
            "state"         => Service_Data_Review::REVIEW_ING,
            "uid"           => $originApackage['uid'], 
            "sop_uid"       => OPERATOR,
            "work_id"       => intval($distinApackage["id"]),
            "update_time"   => time(),
            "create_time"   => time(),
            "ext" => json_encode(array(
                "transfer_type" => 2,
                "origin_apackage_id" => $originApackage["id"],
                "origin_abroadplan_id" => $originApackage["abroadplan_id"],
                "distin_apackage_id" => $distinApackage["id"],
                "distin_abroadplan_id" => $distinApackage["abroadplan_id"],
                "transfer_schedule_nums" => $transferScheduleNums > 0 ? $transferScheduleNums: 0,
                "transfer_remark" => "",
            )),
        );
        $ret = $daoReview->insertRecords($reviewProfile);
        if ($ret == false) {
            $this->daoAporderpackage->rollback();
            return false;
        }
        $this->daoAporderpackage->commit();
        return true;
    }        
    
    // 列表
    public function getListByConds($conds, $field = array(), $indexs = null, $appends = null) {
        $field = empty($field) || !is_array($field) ? $this->daoAporderpackage->arrFieldsMap : $field;
        $lists = $this->daoAporderpackage->getListByConds($conds, $field, $indexs, $appends);
        if (empty($lists)) {
            return array();
        }
        return $lists;
    }    

    // 单独一项
    public function getRecordByConds($conds, $field = array(), $indexs = null, $appends = null) {
        $field = empty($field) || !is_array($field) ? $this->daoAporderpackage->arrFieldsMap : $field;
        $Record = $this->daoAporderpackage->getRecordByConds($conds, $field, $indexs, $appends);
        if (empty($Record)) {
            return array();
        }
        return $Record;
    }

    // 总数
    public function getTotalByConds($conds) {
        return  $this->daoAporderpackage->getCntByConds($conds);
    }
}