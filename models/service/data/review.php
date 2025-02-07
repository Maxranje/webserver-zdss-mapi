<?php

class Service_Data_Review {

    const REVIEW_SUC    = 1; // 成功
    const REVIEW_REF    = 2; // 拒绝
    const REVIEW_ING    = 3; // 执行中

    const REVIEW_TYPE_RECHARGE              = 1; // 充值
    const REVIEW_TYPE_REFUND                = 2; // 退款
    const REVIEW_TYPE_APACKAGE_CREATE       = 3; // 计划服务创建
    const REVIEW_TYPE_APACKAGE_DURATION     = 4; // 计划服务课时调整
    const REVIEW_TYPE_APACKAGE_DONE         = 5; // 服务完结
    const REVIEW_TYPE_APACKAGE_TRANSFER     = 6; // 服务结转

    private $daoReview ;
    
    public function __construct() {
        $this->daoReview = new Dao_Review () ;
    }

    // 根据ID获取班级信息
    public function getReviewById ($id){
        $arrConds = array(
            'id'  => $id,
        );

        $arrFields = $this->daoReview->arrFieldsMap;

        $Review = $this->daoReview->getRecordByConds($arrConds, $arrFields);
        if (empty($Review)) {
            return array();
        }

        return $Review;
    }

    // 根据业务id获取审核信息
    public function getLastReviewByWorkIds ($workIds, $type = 0){
        $arrConds = array(
            sprintf("work_id in (%s)", implode(",", $workIds)),
        );
        if ($type > 0) {
            $arrConds["type"] = $type;
        }
        $arrFields = $this->daoReview->arrFieldsMap;

        $reviewList = $this->daoReview->getListByConds($arrConds, $arrFields);
        if (empty($reviewList)) {
            return array();
        }
        $lists = array();
        foreach ($reviewList as $item) {
            if (empty($lists[$item["work_id"]])) {
                $lists[$item["work_id"]] = $item;
                continue;
            }
            if ($lists[$item["work_id"]]['id'] > $item['id'] ) {
                continue;
            }
            $lists[$item["work_id"]] = $item;
        }
        return $lists;
    }

    // 充值工单处理
    public function rechargeHandle ($id, $userInfo, $capital, $remark, $state) {
        $this->daoReview->startTransaction();
        $uid = intval($userInfo['uid']);

        // 通过才会操作, 否则就是更新记录
        if ($state == self::REVIEW_SUC) {
            $userInfoExt = empty($userInfo['ext']) ? array(): json_decode($userInfo['ext'], true);
            // 更新用户数据
            if (!isset($userInfoExt['total_balance'])){
                $userInfoExt['total_balance'] = 0;    
            }
            $userInfoExt['total_balance'] += $capital["capital"];

            $profile = array(
                sprintf("balance=balance+%d", $capital['capital']),
                'update_time' => time(),
                'ext' => json_encode($userInfoExt),
            );

            $daoUser = new Dao_User();
            $ret = $daoUser->updateByConds(array("uid"=> $uid), $profile);
            if ($ret == false) {
                $this->daoReview->rollback();
                return false;
            }
        }

        // 更新记录表数据
        $daoCapital = new Dao_Capital();
        $profile = array(
            "state" => $state,
            "rop_uid" => OPERATOR,
            "update_time" => time(),
        );
        $ret = $daoCapital->updateByConds(array("id"=> intval($capital['id'])), $profile);
        if ($ret == false) {
            $this->daoReview->rollback();
            return false;
        }

        // 更新审核记录
        $profile = array(
            "state" => $state,
            "rop_uid" => OPERATOR,
            "remark" => $remark,
            "update_time" => time(),
        );
        $ret = $this->daoReview->updateByConds(array("id" => $id), $profile);
        if ($ret == false) {
            $this->daoReview->rollback();
            return false;
        }

        $this->daoReview->commit();
        return true;
    }

    // 学生退款
    public function refundHandle($id, $userInfo, $capital, $remark, $state) {
        $this->daoReview->startTransaction();
        $uid = intval($userInfo['uid']);

        // 审批通过才是更新
        if ($state == self::REVIEW_SUC) {
            $userInfoExt = empty($userInfo['ext']) ? array(): json_decode($userInfo['ext'], true);
            $userInfoExt['total_balance'] -= intval($capital["capital"]);

            // 更新用户信息
            $daoUser = new Dao_User();
            $profile = array(
                sprintf("balance=balance-%d", intval($capital["capital"])),
                'update_time' => time(),
                'ext' => json_encode($userInfoExt),
            );
            $ret = $daoUser->updateByConds(array("uid"=> $uid), $profile);
            if ($ret == false) {
                $this->daoReview->rollback();
                return false;
            }
        }

        // 更新记录表数据
        $daoCapital = new Dao_Capital();
        $profile = array(
            "state" => $state,
            "rop_uid" => OPERATOR,
            "update_time" => time(),
        );
        $ret = $daoCapital->updateByConds(array("id"=> intval($capital['id'])), $profile);
        if ($ret == false) {
            $this->daoReview->rollback();
            return false;
        }

        // 更新审核记录
        $profile = array(
            "state" => $state,
            "rop_uid" => OPERATOR,
            "remark" => $remark,
            "update_time" => time(),
        );
        $ret = $this->daoReview->updateByConds(array("id" => $id), $profile);
        if ($ret == false) {
            $this->daoReview->rollback();
            return false;
        }

        $this->daoReview->commit();
        return true;
    }

    // 计划服务创建
    public function apackageCreateHandle($id, $userInfo, $apackageInfo, $abroadplanInfo, $remark, $state) {
        $this->daoReview->startTransaction();
        $uid = intval($userInfo['uid']);
        $daoChange = new Dao_Orderchange();
        $daoUser = new Dao_User();
        $daoApackage = new Dao_Aporderpackage();

        // 审批通过才是更新
        if ($state == self::REVIEW_SUC) {
            // 订单更新状态
            $profile = array(
                "state" => Service_Data_Aporderpackage::APORDER_STATUS_ABLE,
                "update_time" => time(),
            );
            $conds = array(
                "id" => intval($apackageInfo["id"]),
            );
            $ret = $daoApackage->updateByConds($conds, $profile);
            if ($ret == false) {
                $this->daoReview->rollback();
                return false;
            }
            // 更新用户信息
            $profile = array(
                sprintf("balance=balance-%d", intval($apackageInfo["price"])),
                'update_time' => time(),
            );
            $ret = $daoUser->updateByConds(array("uid"=> $uid), $profile);
            if ($ret == false) {
                $this->daoReview->rollback();
                return false;
            }
        } else if ($state == self::REVIEW_REF) { // 拒绝
            // 订单更新状态
            $profile = array(
                "state" => Service_Data_Aporderpackage::APORDER_STATUS_ABLE_REFUES,
                "update_time" => time(),
            );
            $conds = array(
                "id" => intval($apackageInfo["id"]),
            );
            $ret = $daoApackage->updateByConds($conds, $profile);
            if ($ret == false) {
                $this->daoReview->rollback();
                return false;
            }
        }
        // 服务变更记录
        $changePorfile = array(
            "order_id"          => 0, 
            "student_uid"       => intval($uid), 
            "type"              => Service_Data_Orderchange::CHANGE_APORDER_PACKAGE_CREATE,
            "balance"           => intval($apackageInfo['price']),
            "duration"          => $apackageInfo['schedule_nums'],
            "order_info"        => json_encode(array(
                "abroadplan_name" => $abroadplanInfo["name"], 
                "review_state" => $state,
                "review_id" => $id,
            )),
            "operator"          => OPERATOR, 
            'update_time'       => time(),
            'create_time'       => time(),
        );

        $ret = $daoChange->insertRecords($changePorfile);
        if ($ret == false) {
            $this->daoReview->rollback();
            return false;
        }

        // 更新审核记录
        $profile = array(
            "state" => $state,
            "rop_uid" => OPERATOR,
            "remark" => $remark,
            "update_time" => time(),
        );
        $ret = $this->daoReview->updateByConds(array("id" => $id), $profile);
        if ($ret == false) {
            $this->daoReview->rollback();
            return false;
        }

        $this->daoReview->commit();
        return true;
    }

    // 留学计划订单加课时
    public function apackageDurationAddHandle($id, $userInfo, $apackageInfo, $abroadplanInfo, $review, $remark, $state) {
        $uid = intval($userInfo['uid']);
        // review中记录新增价格
        $this->daoReview->startTransaction();

        $daoChange = new Dao_Orderchange();
        $daoApackage = new Dao_Aporderpackage();

        $oldScheduleNums = floatval($apackageInfo["schedule_nums"]);
        $chgScheduleNums = floatval($review['ext']['schedule_nums']);

        $newScheduleNums = $oldScheduleNums + $chgScheduleNums;

        // 审批后更新为有效状态
        $profile = array(
            "update_time" => time(),
            "state" => Service_Data_Aporderpackage::APORDER_STATUS_ABLE,
        );
        if ($state == self::REVIEW_SUC) {
            $profile['schedule_nums'] = $newScheduleNums;
        }
        $conds = array(
            "id" => intval($apackageInfo["id"]),
        );
        $ret = $daoApackage->updateByConds($conds, $profile);
        if ($ret == false) {
            $this->daoReview->rollback();
            return false;
        }
        // 订单变更记录
        $changePorfile = array(
            "order_id"          => 0, 
            "student_uid"       => intval($uid), 
            "type"              => Service_Data_Orderchange::CHANGE_APORDER_DURATION_ADD,
            "balance"           => 0,
            "duration"          => $chgScheduleNums,
            "order_info"        => json_encode(array(
                "abroadplan_name"   => $abroadplanInfo["name"], 
                "review_state"      => $state,
                "review_id"         => $id,
                "old_schedule_nums" => $oldScheduleNums,
                "new_schedule_nums" => $newScheduleNums,
            )),
            "operator"          => OPERATOR, 
            'update_time'       => time(),
            'create_time'       => time(),
        );

        $ret = $daoChange->insertRecords($changePorfile);
        if ($ret == false) {
            $this->daoReview->rollback();
            return false;
        }

        // 更新审核记录
        $profile = array(
            "state" => $state,
            "rop_uid" => OPERATOR,
            "remark" => $remark,
            "update_time" => time(),
        );
        $ret = $this->daoReview->updateByConds(array("id" => $id), $profile);
        if ($ret == false) {
            $this->daoReview->rollback();
            return false;
        }

        $this->daoReview->commit();
        return true;
    }

    // 留学计划服务完结
    public function apackageDoneHandle($id, $userInfo, $apackageInfo, $abroadplanInfo, $remark, $state) {
        $this->daoReview->startTransaction();
        $uid = intval($userInfo['uid']);
        $daoChange = new Dao_Orderchange();
        $daoApackage = new Dao_Aporderpackage();

        // 更新状态
        $profile = array(
            "state" => $state == self::REVIEW_REF ? 
                Service_Data_Aporderpackage::APORDER_STATUS_ABLE : // 还原回去
                Service_Data_Aporderpackage::APORDER_STATUS_DONE,
            "update_time" => time(),
        );
        $conds = array(
            "id" => intval($apackageInfo["id"]),
        );
        $ret = $daoApackage->updateByConds($conds, $profile);
        if ($ret == false) {
            $this->daoReview->rollback();
            return false;
        }    

        // 服务变更记录
        $changePorfile = array(
            "order_id"          => 0, 
            "student_uid"       => intval($uid), 
            "type"              => Service_Data_Orderchange::CHANGE_APORDER_PACKAGE_OVER,
            "balance"           => 0,
            "duration"          => 0,
            "order_info"        => json_encode(array(
                "abroadplan_name" => $abroadplanInfo["name"], 
                "review_state" => $state,
                "review_id" => $id,
            )),
            "operator"          => OPERATOR, 
            'update_time'       => time(),
            'create_time'       => time(),
        );

        $ret = $daoChange->insertRecords($changePorfile);
        if ($ret == false) {
            $this->daoReview->rollback();
            return false;
        }

        // 更新审核记录
        $profile = array(
            "state" => $state,
            "rop_uid" => OPERATOR,
            "remark" => $remark,
            "update_time" => time(),
        );
        $ret = $this->daoReview->updateByConds(array("id" => $id), $profile);
        if ($ret == false) {
            $this->daoReview->rollback();
            return false;
        }

        $this->daoReview->commit();
        return true;
    }

    // 计划服务结转
    public function apackageTransferHandle($id, $userInfo, $apackageInfo, $abroadplanInfo, $remark, $review, $state) {
        $this->daoReview->startTransaction();
        $uid = intval($userInfo['uid']);
        $reviewExt = $review['ext'];
        $daoChange = new Dao_Orderchange();
        $daoUser = new Dao_User();
        $daoApackage = new Dao_Aporderpackage();

        // 审批通过才是更新
        if ($state == self::REVIEW_SUC) {
            // origin apackage done
            $conds = array(
                "id" => intval($reviewExt["origin_apackage_id"]),
            );
            $up = array(
                "state" => Service_Data_Aporderpackage::APORDER_STATUS_TRANS,
                "update_time" => time(),
            );
            $ret = $daoApackage->updateByConds($conds, $up);
            if ($ret == false) {
                $this->daoReview->rollback();
                return false;
            }

            // 目的服务处理
            if ($reviewExt['transfer_type'] == 1) { // 新建服务
                // new apackage add duration and change state
                $oldScheduleNums = floatval($apackageInfo["schedule_nums"]);
                $chgScheduleNums = floatval($review['ext']['transfer_schedule_nums']);
                $newScheduleNums = $oldScheduleNums + $chgScheduleNums;

                $conds = array(
                    "id" => intval($apackageInfo["id"]),
                );
                $up = array(
                    "state" => Service_Data_Aporderpackage::APORDER_STATUS_ABLE,
                    "update_time" => time(),
                    "schedule_nums" => $newScheduleNums,
                );
                $ret = $daoApackage->updateByConds($conds, $up);
                if ($ret == false) {
                    $this->daoReview->rollback();
                    return false;
                }

                // 更新用户信息
                if (isset($apackageInfo["price"]) && $apackageInfo["price"] > 0) {
                    $profile = array(
                        sprintf("balance=balance-%d", intval($apackageInfo["price"])),
                        'update_time' => time(),
                    );
                    $ret = $daoUser->updateByConds(array("uid"=> $uid), $profile);
                    if ($ret == false) {
                        $this->daoReview->rollback();
                        return false;
                    }  
                }
            } else {
                $oldScheduleNums = floatval($apackageInfo["schedule_nums"]);
                $chgScheduleNums = floatval($review['ext']['transfer_schedule_nums']);
                $newScheduleNums = $oldScheduleNums + $chgScheduleNums;

                // 审批后更新为有效状态
                $profile = array(
                    "update_time" => time(),
                    "schedule_nums" => $newScheduleNums,
                );
                $conds = array(
                    "id" => intval($apackageInfo["id"]),
                );
                $ret = $daoApackage->updateByConds($conds, $profile);
                if ($ret == false) {
                    $this->daoReview->rollback();
                    return false;
                }
            }
        } else if ($state == self::REVIEW_REF) { // 拒绝
            // origin apackage done
            $conds = array(
                "id" => intval($reviewExt["origin_apackage_id"]),
            );
            $up = array(
                "state" => Service_Data_Aporderpackage::APORDER_STATUS_ABLE,
                "update_time" => time(),
            );
            $ret = $daoApackage->updateByConds($conds, $up);
            if ($ret == false) {
                $this->daoReview->rollback();
                return false;
            }

            // 目的服务处理
            if ($reviewExt['transfer_type'] == 1) { // 新建服务
                // new apackage add duration and change state
                $conds = array(
                    "id" => intval($apackageInfo["id"]),
                );
                $up = array(
                    "state" => Service_Data_Aporderpackage::APORDER_STATUS_TRANS_REFUES,
                    "update_time" => time(),
                );
                $ret = $daoApackage->updateByConds($conds, $up);
                if ($ret == false) {
                    $this->daoReview->rollback();
                    return false;
                }             
            }
        }
        // 服务变更记录
        $changePorfile = array(
            "order_id"          => 0, 
            "student_uid"       => intval($uid), 
            "type"              => Service_Data_Orderchange::CHANGE_APORDER_PACKAGE_TRANS,
            "balance"           => $reviewExt['transfer_type'] == 1 ? intval($apackageInfo['price']) : 0,
            "duration"          => $reviewExt['transfer_schedule_nums'],
            "order_info"        => json_encode(array(
                "abroadplan_name" => $abroadplanInfo["name"], 
                "review_state" => $state,
                "origin_apackage_id" => $reviewExt['origin_apackage_id'],
                "origin_abroadplan_id" => $reviewExt["origin_abroadplan_id"],
                "distin_apackage_id" => $reviewExt['distin_apackage_id'],
                "distin_abroadplan_id" => $reviewExt["distin_abroadplan_id"],
                "transfer_remark" => empty($reviewExt['transfer_remark']) ? "" : $reviewExt['transfer_remark'],
            )),
            "operator"          => OPERATOR, 
            'update_time'       => time(),
            'create_time'       => time(),
        );

        $ret = $daoChange->insertRecords($changePorfile);
        if ($ret == false) {
            $this->daoReview->rollback();
            return false;
        }

        // 更新审核记录
        $profile = array(
            "state" => $state,
            "rop_uid" => OPERATOR,
            "remark" => $remark,
            "update_time" => time(),
        );
        $ret = $this->daoReview->updateByConds(array("id" => $id), $profile);
        if ($ret == false) {
            $this->daoReview->rollback();
            return false;
        }

        $this->daoReview->commit();
        return true;
    }    


    public function getListByConds($conds, $field = array(), $indexs = null, $appends = null) {
        $field = empty($field) || !is_array($field)? $this->daoReview->arrFieldsMap : $field;
        $lists = $this->daoReview->getListByConds($conds, $field, $indexs, $appends);
        if (empty($lists)) {
            return array();
        }
        return $lists;
    }

    public function getTotalByConds($conds) {
        return  $this->daoReview->getCntByConds($conds);
    }
}