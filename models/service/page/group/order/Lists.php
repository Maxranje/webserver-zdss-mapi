<?php

class Service_Page_Group_Order_Lists extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $groupId = empty($this->request['group_id']) ? 0 : intval($this->request['group_id']);
        if ($groupId <= 0) {
            return array();
        }

        $conds = array(
            "group_id" => $groupId,
        );
        $serviceData = new Service_Data_Curriculum();
        $orderIds = $serviceData->getListByConds($conds, array("distinct(order_id) as order_id"));
        $orderIds = Zy_Helper_Utils::arrayInt($orderIds, "order_id");
        if (empty($orderIds)) {
            return array();
        }

        $serviceData = new Service_Data_Order();
        $lists = $serviceData->getOrderByIds($orderIds);
        if (empty($lists)) {
            return array();
        }
        
        return array(
            'rows' => $this->formatDefault($lists),
        );
    }

    // 默认格式化
    private function formatDefault ($lists) {
        $studentUids = Zy_Helper_Utils::arrayInt($lists, 'student_uid');
        $subjectIds  = Zy_Helper_Utils::arrayInt($lists, 'subject_id');
        $bpdis       = Zy_Helper_Utils::arrayInt($lists, 'bpid');
        $cids        = Zy_Helper_Utils::arrayInt($lists, 'cid');

        $serviceData = new Service_Data_Subject();
        $subjectInfos = $serviceData->getListByConds(array(sprintf("id in (%s)", implode(",", $subjectIds))));
        $subjectInfos = array_column($subjectInfos, null, 'id');

        $serviceData = new Service_Data_Profile();
        $userInfos = $serviceData->getListByConds(array(sprintf("uid in (%s)", implode(",", $studentUids))));
        $userInfos = array_column($userInfos, null, 'uid');

        $serviceData = new Service_Data_Birthplace();
        $birthplaces = $serviceData->getBirthplaceByIds($bpdis);
        $birthplaces = array_column($birthplaces, null, 'id');

        $serviceData = new Service_Data_Clasze();
        $claszes = $serviceData->getClaszeByIds($cids);
        $claszes = array_column($claszes, null, 'id');

        $result = array();
        foreach ($lists as $v) {
            if (empty($subjectInfos[$v['subject_id']]['name'])) {
                continue;
            }
            if (empty($userInfos[$v['student_uid']]['nickname'])) {
                continue;
            }
            
            $extra = json_decode($v['ext'], true);
            $item = array();
            $item['order_id']       = $v['order_id'] ;
            $item['student_name']   = $userInfos[$v['student_uid']]['nickname'];
            $item['subject_name']   = $subjectInfos[$v['subject_id']]['name'];
            $item['student_uid']    = intval($v['student_uid']);
            $item['update_time']    = date("Y年m月d日 H:i",$v['update_time']);
            $item['create_time']    = date("Y年m月d日 H:i",$v['create_time']);
            $item['balance']        = sprintf("%.2f", $v['balance'] / 100);
            $item['price']          = sprintf("%.2f", $v['price'] / 100);

            $item['birthplace']     = empty($birthplaces[$v['bpid']]['name']) ? "" : $birthplaces[$v['bpid']]['name'];
            $item['clasze_name']    = empty($claszes[$v['cid']]['name']) ? "" : $claszes[$v['cid']]['name'];
            $item['origin_balance'] = sprintf("%.2f", $extra['origin_balance'] / 100);
            $item['real_balance']   = sprintf("%.2f", $extra['real_balance'] / 100);
            $item['origin_price']   = sprintf("%.2f", $extra['origin_price'] / 100);
            $item['real_price']     = sprintf("%.2f", $extra['real_price'] / 100);
            $item['schedule_nums']  = $extra['schedule_nums'];
            $item['isfree']         = empty($v['isfree']) ? 0 : 1;
            $item['discount_info']  = "";
            if (!empty($v['discount_z'])) {
                $item['discount_info'] .= "折扣(" . $v['discount_z'] . "%) ";
            } 
            if (!empty($v['discount_j'])) {
                $item['discount_info'] .= sprintf("减免(%.2f元)", $v['discount_j'] / 100);
            }

            $item['change_balance']     = empty($extra['change_balance']) ? "0.00" : sprintf("%.2f", $extra['change_balance'] / 100);
            $item['remark']             = empty($extra['remark']) ? "" : $extra['remark'];
            $result[] = $item;
        }
        return $result;
    }
}