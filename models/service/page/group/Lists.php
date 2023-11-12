<?php

class Service_Page_Group_Lists extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $name           = empty($this->request['name']) ? "" : trim($this->request['name']);
        $state          = empty($this->request['state']) || !in_array($this->request['state'], [Service_Data_Group::GROUP_ABLE,Service_Data_Group::GROUP_DISABLE]) ? 0 : intval($this->request['state']);
        $studentUid     = empty($this->request['student_uid']) ? "" : intval($this->request['student_uid']);
        $areaOperator   = empty($this->request['area_operator']) ? 0 : intval($this->request['area_operator']);
        $isSelect       = empty($this->request['is_select']) ? false : true;
        $pn             = empty($this->request['page']) ? 0 : intval($this->request['page']);
        $rn             = empty($this->request['perPage']) ? 0 : intval($this->request['perPage']);
        $pn             = ($pn-1) * $rn;

        $conds = array();
        
        if ($studentUid > 0) {
            $serviceData = new Service_Data_Curriculum();
            $lists = $serviceData->getListByConds(array("student_uid" => $studentUid), array("group_id"));
            if (empty($lists)) {
                return array();
            }

            $groupIds = Zy_Helper_Utils::arrayInt($lists, "group_id");

            $conds[] = sprintf("id in (%s)", implode(",", $groupIds));
        }

        if (!empty($name)) {
            $conds[] = "name like '%".$name."%'";
        }

        if ($state > 0) {
            $conds["state"] = $state;
        }

        if ($areaOperator > 0) {
            $conds['area_operator'] = $areaOperator;
        }

        $arrAppends[] = 'order by id desc';

        if (!$isSelect) {
            $arrAppends[] = "limit {$pn} , {$rn}";
        }

        $serviceGroup = new Service_Data_Group();
        $lists = $serviceGroup->getListByConds($conds, false, null, $arrAppends);
        $lists = $this->formatDefault($lists);

        if ($isSelect) {
            return $this->formatSelect($lists);
        }

        $total = $serviceGroup->getTotalByConds($conds);
        return array(
            'rows' => $lists,
            'total' => $total,
        );
    }

    // 格式化基础内容
    private function formatDefault($lists) {
        if (empty($lists)) {
            return array();
        }

        $groupIds   = Zy_Helper_Utils::arrayInt($lists, "id");
        $uids       = Zy_Helper_Utils::arrayInt($lists, "area_operator");
        $subjectIds = Zy_Helper_Utils::arrayInt($lists, 'subject_id');
        $cids       = Zy_Helper_Utils::arrayInt($lists, 'cid');

        // 区域管理员信息
        $serviceData = new Service_Data_Profile();
        $userInfos = $serviceData->getUserInfoByUids($uids);
        $userInfos = array_column($userInfos, null, 'uid');

        $serviceData = new Service_Data_Subject();
        $subjects = $serviceData->getSubjectByIds($subjectIds);
        $subjects = array_column($subjects, null, 'id');

        $serviceData = new Service_Data_Clasze();
        $claszes = $serviceData->getClaszeByIds($cids);
        $claszes = array_column($claszes, null, 'id');

        // 获取订单量
        $serviceData = new Service_Data_Schedule();
        $scheduleInfo = $serviceData->getSchduleCountByGroup($groupIds);
        $scheduleInfo = array_column($scheduleInfo, null, 'group_id');

        $result = array();
        foreach ($lists as $v) {
            if (empty($userInfos[$v['area_operator']]['nickname'])) {
                continue;
            }
            if (empty($subjects[$v['subject_id']]['name'])) {
                continue;
            }
            if (empty($claszes[$v['cid']]['name'])) {
                continue;
            }
            
            $item = $v;
            $item['state'] = intval($item['state']);
            $item['subject_name'] = $subjects[$v['subject_id']]['name'];
            $item['clasze_name'] = $claszes[$v['cid']]['name'];
            $item['area_operator_name'] = $userInfos[$v['area_operator']]['nickname'];
            $item['schedule_count'] = empty($scheduleInfo[$v['id']]) ? 0 : $scheduleInfo[$v['id']]['count'];
            $item['create_time'] = date("Y年m月d日", $item['create_time']);
            $item['update_time'] = date("Y年m月d日", $item['update_time']);

            $result[] = $item;
        }
        return $result;
    }

    private function formatSelect($lists) {
        $options = array();
        foreach ($lists as $item) {
            $options[] = array(
                'label' => sprintf("%s - %s", $item['name'], $item['identify']),
                'value' => $item['id'],
            );
        }
        return array('options' => array_values($options));
    }
}