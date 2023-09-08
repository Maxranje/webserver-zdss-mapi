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

        $serviceData = new Service_Data_Column();

        $lists = $serviceData->getColumnBySId($subjectId);
        return $this->formatSelect($lists, $subjectInfo, $subjectParentInfo);
    }

    private function formatSelect($lists, $subjectInfo, $subjectParentInfo) {
        if (empty($lists)) {
            return array();
        }

        $teacherUids = Zy_Helper_Utils::arrayInt($lists, "teacher_uid");
        $serviceData = new Service_Data_Profile();
        $userInfos = $serviceData->getUserInfoByUids($teacherUids);
        $userInfos = array_column($userInfos, null, "uid");


        $children = array();
        foreach ($lists as $item) {
            if (empty($userInfos[$item['teacher_uid']]['name'])) {
                continue;
            }
            if ($userInfos[$item['teacher_uid']]['state'] != 1) {
                continue;
            }
            $children[] = [
                'label' => $userInfos[$item['teacher_uid']]['nickname'],
                'value' => sprintf("%s_%s", $subjectInfo['id'], $item['teacher_uid']),
            ];
        }

        if (empty($children)) {
            return array();
        }

        $options = array(
            "label" => sprintf("%s / %s", $subjectParentInfo['name'], $subjectInfo['name']),
            "value" => $subjectInfo['id'],
            "children" => $children,
        );
        return array($options);
    }
}