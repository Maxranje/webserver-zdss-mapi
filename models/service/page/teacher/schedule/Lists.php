<?php

class Service_Page_Teacher_Schedule_Lists extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $groupId        = empty($this->request['group_id']) ? 0 : intval($this->request['group_id']);
        $isSelect       = empty($this->request['is_select']) ? false : true;

        $conds = array(
            'type' => Service_Data_Profile::USER_TYPE_TEACHER,
        );

        if ($groupId <= 0) {
            return array();
        }

        // 获取班级绑定的科目
        $serviceData = new Service_Data_Group();
        $group = $serviceData->getGroupById($groupId);
        if (empty($group)) {
            return array();
        }
        $psid = intval($group['subject_id']);

        // 通过科目找二级分类
        $serviceData = new Service_Data_Subject();
        $subjectInfos = $serviceData->getSubjectByParentID($psid);
        if (empty($subjectInfos)) {
            return array();
        }
        $subjectIds = Zy_Helper_Utils::arrayInt($subjectInfos, "id");

        // 获取parent subject_id信息
        $parentSubject = $serviceData->getSubjectById($psid);
        if (empty($parentSubject)) {
            return array();
        }

        // 通过二级分类找所有的绑定的column
        $serviceData = new Service_Data_Column();
        $columnList = $serviceData->getListByConds(array(sprintf("subject_id in (%s)", implode(",", $subjectIds))));
        if (empty($columnList)) {
            return array();
        }
        $teacherUids = Zy_Helper_Utils::arrayInt($columnList, "teacher_uid");

        $serviceData = new Service_Data_Profile();
        $conds = array(
            sprintf("uid in (%s)", implode(",", $teacherUids)),
            "state" => Service_Data_Profile::STUDENT_ABLE,
        );
        $arrAppends[] = 'order by uid desc';

        $lists = $serviceData->getListByConds($conds, array("uid", "nickname"), NULL, $arrAppends);
        return $this->formatSelect($lists, $columnList, $subjectInfos, $parentSubject);
    }

    private function formatSelect($lists, $columnList, $subjectInfos, $parentSubject) {
        if (empty($lists)) {
            return array();
        }

        $lists = array_column($lists, null, "uid");
        $subjectInfos = array_column($subjectInfos, null, "id");

        $options = array();
        // 格式化数据
        foreach ($columnList as $item) {
            if (empty($lists[$item['teacher_uid']])) {
                continue;
            }
            if (empty($subjectInfos[$item['subject_id']])) {
                continue;
            }
            $teacher = $lists[$item['teacher_uid']];
            $subject = $subjectInfos[$item['subject_id']];

            if (!isset($options[$teacher['uid']])) {
                $options[$teacher['uid']] = [
                    'label' => $teacher['nickname'],
                    'value' => $teacher['uid'],
                    "children" => array(),
                ];
            }
            // 父节点
            if (empty($options[$teacher['uid']]['children'][$parentSubject['id']])) {
                $options[$teacher['uid']]['children'][$parentSubject['id']] = array(
                    'label' => $parentSubject['name'],
                    'value' => $parentSubject['id'],
                    "children" => array(),
                );
            }
            $options[$teacher['uid']]['children'][$parentSubject['id']]['children'][] = array(
                'label' => $subject['name'],
                'value' => $subject['id'] . "_" . $teacher['uid'],
            );
        }

        foreach ($options as &$item) {
            $item['children'] = array_values($item['children']);
        }
        return array_values($options);
    }
}