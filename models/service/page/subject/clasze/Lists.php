<?php

class Service_Page_Subject_Clasze_Lists extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $pn             = empty($this->request['page']) ? 1 : intval($this->request['page']);
        $rn             = empty($this->request['perPage']) ? 20 : intval($this->request['perPage']);
        $studentUid     = empty($this->request['student_uid']) ? 0 : intval($this->request['student_uid']);
        $type           = empty($this->request['type']) ? 0 : intval($this->request['type']);

        $pn = ($pn-1) * $rn;

        if (!in_array($type, [1,2])) {
            return array();
        }

        if ($type == 1) {
            if ($studentUid <= 0) {
                return array();
            }
            $serviceUser = new Service_Data_Profile();
            $userInfo = $serviceUser->getUserInfoByUid($studentUid);
            if (!isset($userInfo['bpid']) && intval($userInfo['bpid']) <= 0) {
                throw new Zy_Core_Exception(405, "操作失败, 该学员信息不存在或没有配置生源地信息");
            }
            $bpid = intval($userInfo['bpid']);

            if ($bpid <= 0) {
                return array();
            }
    
            $serviceClaszeMap = new Service_Data_Claszemap();
            $mapLists = $serviceClaszeMap->getListByConds(array("bpid" => $bpid));
            if (empty($mapLists)) {
                return array();
            }

            return $this->formatBase($bpid, $mapLists);
        }

        // 按照subject查所有
        if  ($type == 2) {
            $serviceClaszeMap = new Service_Data_Claszemap();
            $mapLists = $serviceClaszeMap->getListByConds(array(), array("subject_id", "cid"), null, array("group by subject_id, cid"));
            if (empty($mapLists)) {
                return array();
            }

            return $this->formatSimple ($mapLists) ;
        }
    }

    public function formatBase($bpid, $lists) {
        if (empty($lists)) {
            return array();
        }

        $cids = Zy_Helper_Utils::arrayInt($lists, "cid");
        $subjectIds = Zy_Helper_Utils::arrayInt($lists, "subject_id");

        $serviceData = new Service_Data_Clasze();
        $cInfos = $serviceData->getClaszeByIds($cids);
        $cInfos = array_column($cInfos, null, "id");

        $serviceData = new Service_Data_Birthplace();
        $bInfo = $serviceData->getBirthplaceById($bpid);
        if (empty($bInfo)) {
            return array();
        }

        $serviceData = new Service_Data_Subject();
        $sInfos = $serviceData->getSubjectByIds($subjectIds);
        $sInfos = array_column($sInfos, null, "id");

        $result = array(
            'label' => $bInfo['name'],
            "children" => array(),
        );
        foreach ($lists as $item) {
            if (empty($sInfos[$item['subject_id']]['name'])) {
                continue;
            }
            if (empty($cInfos[$item['cid']]['name'])) {
                continue;
            }

            if (empty($result['children'][$item['subject_id']])) {
                $result['children'][$item['subject_id']] = array(
                    'label' => $sInfos[$item['subject_id']]['name'],
                    "children" => array(),
                );
            }
            $result['children'][$item['subject_id']]['children'][$item['cid']] = array(
                'label' => $cInfos[$item['cid']]['name'],
                'value' => $item['id'],
            );
        }

        foreach ($result['children'] as &$item) {
            $item['children'] = array_values($item['children']);
        }

        $result['children'] = array_values($result['children']);
        return array($result);
    }


    public function formatSimple($lists) {
        if (empty($lists)) {
            return array();
        }

        $subjectIds= Zy_Helper_Utils::arrayInt($lists, "subject_id");
        $cids= Zy_Helper_Utils::arrayInt($lists, "cid");

        $serviceData = new Service_Data_Subject();
        $subjectInfos = $serviceData->getSubjectByIds($subjectIds);
        $subjectInfos = array_column($subjectInfos, null, "id");

        $serviceData = new Service_Data_Clasze();
        $claszeInfos = $serviceData->getClaszeByIds($cids);
        $claszeInfos = array_column($claszeInfos, null, "id");

        $result = array();
        foreach ($lists as $item) {
            if (empty($subjectInfos[$item['subject_id']])) {
                continue;
            }
            if (empty($claszeInfos[$item['cid']])) {
                continue;
            }

            if (!isset($result[$item['subject_id']])) {
                $result[$item['subject_id']] = array(
                    'label' => $subjectInfos[$item['subject_id']]['name'],
                    'children' => array(),
                );
            }
            $result[$item['subject_id']]['children'][] = array(
                'label' => $claszeInfos[$item['cid']]['name'],
                'value' => sprintf("%s_%s", $item['subject_id'], $item['cid']),
            );
        }
        return array_values($result);
    }
}