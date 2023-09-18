<?php

class Service_Page_Column_Lists extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $teacherUid = empty($this->request['teacher_uid']) ? 0 : intval($this->request['teacher_uid']);
        if ($teacherUid <= 0) {
            return array();
        }
        
        $serviceData = new Service_Data_Column();
        $lists = $serviceData->getColumnByTId($teacherUid);
        return array(
            "rows" => $this->formatDefault($lists),
        );
    }

    private function formatDefault ($lists) {
        if (empty($lists)) {
            return array();
        }

        $subjectIds = Zy_Helper_Utils::arrayInt($lists, "subject_id");

        $serviceData = new Service_Data_Subject();
        $subjectInfos = $serviceData->getListByConds(array(sprintf("id in (%s)", implode(",", $subjectIds))));
        $subjectInfos = array_column($subjectInfos, null, 'id');

        $subjectParentIds = Zy_Helper_Utils::arrayInt($subjectInfos, "parent_id");
        $subjectParentInfos = $serviceData->getSubjectByIds($subjectParentIds);
        $subjectParentInfos = array_column($subjectParentInfos, null, 'id');
        
        $result = array();
        foreach ($lists as $item) {
            if (empty($subjectInfos[$item['subject_id']]['name'])) {
                continue;
            }
            if (empty($subjectInfos[$item['subject_id']]['parent_id'])) {
                continue;
            }
            $subjectParentId = $subjectInfos[$item['subject_id']]['parent_id'];
            if (empty($subjectParentInfos[$subjectParentId]['name'])) {
                continue;
            }
            $tmp = array(
                'column_id' => $item['id'], 
                'subject_name' => sprintf("%s/%s", $subjectParentInfos[$subjectParentId]['name'], $subjectInfos[$item['subject_id']]['name']),
                'teacher_uid' => $item['teacher_uid'],
                'subject_id' => $item['subject_id'],
                'price' => sprintf("%.2f", intval($item['price']) / 100),
                'muilt_num' => $item['muilt_num'],
                'muilt_price' => sprintf("%.2f", intval($item['muilt_price']) / 100),
                "price_info" => "",
            );
            $tmp['price_info'] = sprintf("【%s元/%s人】\r\n", $tmp['price'], 1);
            if ($tmp['muilt_num'] > 0) {
                $tmp['price_info'] .= sprintf("【%s元/%s人】\r\n", $tmp['muilt_price'], $tmp['muilt_num']);
            }
            $result[] = $tmp;
        }

        return $result;
    }
}