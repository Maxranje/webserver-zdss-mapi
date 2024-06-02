<?php

class Service_Data_Review {

    const REVIEW_SUC    = 1; // 成功
    const REVIEW_REF    = 2; // 拒绝
    const REVIEW_ING    = 3; // 执行中

    const REVIEW_TYPE_RECHARGE = 1; // 充值
    const REVIEW_TYPE_REFUND = 2; // 退款

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

    // 根据ID获取班级信息
    public function getReviewByWorkIds ($workIds){
        $arrConds = array(
            sprintf("work_id in (%s)", implode(",", $workIds)),
        );

        $arrFields = $this->daoReview->arrFieldsMap;

        $Review = $this->daoReview->getListByConds($arrConds, $arrFields);
        if (empty($Review)) {
            return array();
        }

        return $Review;
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
                'update_time' => time(),
                'ext' => json_encode($userInfoExt),
            );
            if ($capital['plan_id'] <= 0) {
                $profile[] = sprintf("balance=balance+%d", $capital['capital']);
            }

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