<?php

class Service_Page_Group_Order_Aplists extends Zy_Core_Service{

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
        $lists = $serviceData->getAporderByIds($orderIds);
        if (empty($lists)) {
            return array();
        }
        
        return array(
            'rows' => $this->formatDefault($lists),
        );
    }

    // 默认格式化
    private function formatDefault ($lists) {
        $abroadplanIds  = Zy_Helper_Utils::arrayInt($lists, "abroadplan_id");
        $studentUids    = Zy_Helper_Utils::arrayInt($lists, 'student_uid');
        $subjectIds     = Zy_Helper_Utils::arrayInt($lists, 'subject_id');
        $bpdis          = Zy_Helper_Utils::arrayInt($lists, 'bpid');
        $cids           = Zy_Helper_Utils::arrayInt($lists, 'cid');

        $serviceData = new Service_Data_Abroadplan();
        $abroadplanInfos = $serviceData->getAbroadplanByIds($abroadplanIds);
        $abroadplanInfos = array_column($abroadplanInfos, null, 'id');

        $serviceData = new Service_Data_Subject();
        $subjectInfos = $serviceData->getSubjectByIds($subjectIds);
        $subjectInfos = array_column($subjectInfos, null, 'id');

        $serviceData = new Service_Data_Profile();
        $userInfos = $serviceData->getUserInfoByUids($studentUids);
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
            if (empty($abroadplanInfos[$v['abroadplan_id']]['name'])) {
                continue;
            }
            $item = array();
            $item['order_id']           = $v['order_id'] ;
            $item['abroadplan_name']    = $abroadplanInfos[$v['abroadplan_id']]['name'];
            $item['student_name']       = $userInfos[$v['student_uid']]['nickname'];
            $item['subject_name']       = $subjectInfos[$v['subject_id']]['name'];
            $item['student_uid']        = intval($v['student_uid']);
            $item['birthplace']         = empty($birthplaces[$v['bpid']]['name']) ? "" : $birthplaces[$v['bpid']]['name'];
            $item['clasze_name']        = empty($claszes[$v['cid']]['name']) ? "" : $claszes[$v['cid']]['name'];
            $result[] = $item;
        }
        return $result;
    }
}