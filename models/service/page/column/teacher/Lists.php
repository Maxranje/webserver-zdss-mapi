<?php

class Service_Page_Column_Teacher_Lists extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $subjectId      = empty($this->request['subject_id']) ? 0 : intval($this->request['subject_id']);

        if ($subjectId <= 0) {
            return array();
        }

        $serviceData = new Service_Data_Subject();
        $subjectInfo = $serviceData->getSubjectById($subjectId);
        if (empty($subjectInfo)) {
            return array();
        }

        $subjectParentInfo = $serviceData->getSubjectById(intval($subjectInfo['parent_id']));
        if (empty($subjectParentInfo)) {
            return array();
        }

        $subjectBortherInfo = $serviceData->getSubjectByParentID(intval($subjectInfo['parent_id']));
        if (empty($subjectBortherInfo)) {
            return array();
        }

        $subjectIds = Zy_Helper_Utils::arrayInt($subjectBortherInfo, "id");

        $serviceData = new Service_Data_Column();
        $lists = $serviceData->getColumnBySIds($subjectIds);
        return $this->formatSelect($lists, $subjectInfo, $subjectParentInfo, $subjectBortherInfo);
    }

    private function formatSelect($lists, $subjectInfo, $subjectParentInfo, $subjectBortherInfo) {
        if (empty($lists)) {
            return array();
        }

        $teacherUids = Zy_Helper_Utils::arrayInt($lists, "teacher_uid");
        $serviceData = new Service_Data_Profile();
        $userInfos = $serviceData->getUserInfoByUids($teacherUids);
        $userInfos = array_column($userInfos, null, "uid");

        $subjectBortherInfo = array_column($subjectBortherInfo, null, "id");

        $children = array();
        foreach ($lists as $item) {
            if (empty($userInfos[$item['teacher_uid']]['name'])) {
                continue;
            }
            if ($userInfos[$item['teacher_uid']]['state'] == Service_Data_Profile::STUDENT_DISABLE) {
                continue;
            }
            if (empty($subjectBortherInfo[$item['subject_id']]['name'])) {
                continue;
            }

            if (!isset($children[$item['subject_id']])) {
                $children[$item['subject_id']] = array(
                    "label" =>  $subjectBortherInfo[$item['subject_id']]['name'],
                    "children" => array(),
                );
            }

            $children[$item['subject_id']]['children'][] = array(
                'label' => $userInfos[$item['teacher_uid']]['nickname'],
                'value' => sprintf("%s_%s", $item['subject_id'], $item['teacher_uid']),
            );
        }

        if (empty($children)) {
            return array();
        }

        $options = array(
            "label" =>  $subjectParentInfo['name'],
            "children" => array_values($children),
        );
        return array($options);
    }
}