<?php

class Service_Page_Teacher_Lists extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $nickname       = empty($this->request['nickname']) ? "" : $this->request['nickname'];
        $name           = empty($this->request['name']) ? "" : $this->request['name'];
        $phone          = empty($this->request['phone']) ? "" : $this->request['phone'];
        $state          = empty($this->request['state']) ? 0 : intval($this->request['state']);
        $isSelect       = empty($this->request['is_select']) ? false : true;
        $isSubject      = empty($this->request['is_subject']) ? false : true;
        $pn             = empty($this->request['page']) ? 1 : intval($this->request['page']);
        $rn             = empty($this->request['perPage']) ? 20 : intval($this->request['perPage']);
        $pn             = ($pn-1) * $rn;

        $conds = array(
            'type' => Service_Data_Profile::USER_TYPE_TEACHER,
        );

        if (!empty($name)) {
            $conds[] = "name = '$name'";
        }

        if (!empty($nickname)) {
            $conds[] = "nickname like '%".$nickname."%'";
        }

        if (!empty($phone)) {
            $conds[] = "phone = '$phone'";
        }
        
        if ($state > 0) {
            $conds[] = "state = $state";
        }

        $serviceData = new Service_Data_Profile();

        $arrAppends[] = 'order by uid desc';

        if (!$isSelect) {
            $arrAppends[] = "limit {$pn} , {$rn}";
        }   

        $lists = $serviceData->getListByConds($conds, false, NULL, $arrAppends);
        if ($isSelect) {
            return $this->formatSelect($lists, $isSubject);
        }

        $total = $serviceData->getTotalByConds($conds);
        return array(
            'rows' => $lists,
            'total' => $total,
        );
    }

    private function formatSelect($lists, $isSubject) {
        if ($isSubject) {
            return $this->formatSubject($lists);
        } 

        $options = array();
        foreach ($lists as $item) {
            $optionsItem = [
                'label' => $item['nickname'],
                'value' => $item['uid'],
            ];
            $options[] = $optionsItem;
        }
        return $options;
    }

    private function formatSubject($lists) {
        if (empty($lists)) {
            return array();
        }

        $options = array();
        $lists = array_column($lists, null , "uid");
        $uids = Zy_Helper_Utils::arrayInt($lists, "uid");

        // 查询所有的绑定
        $serviceData = new Service_Data_Column();
        $columnInfos = $serviceData->getListByConds(array(sprintf('teacher_uid in (%s)', implode(',', $uids))));
        if (empty($columnInfos)) {
            return array();
        }
        $subjectIds = Zy_Helper_Utils::arrayInt($columnInfos, "subject_id");

        # 查到所有科目名称
        $servicSubject = new Service_Data_Subject();
        $subjectInfos = $servicSubject->getListByConds(array(sprintf("id in (%s)", implode(",", $subjectIds))));
        $subjectInfos = array_column($subjectInfos, null, "id");

        $subjectParentIds = Zy_Helper_Utils::arrayInt($subjectInfos, "parent_id");
        $subjectParentInfos = $servicSubject->getListByConds(array(sprintf("id in (%s)", implode(",", $subjectParentIds))));
        $subjectParentInfos = array_column($subjectParentInfos, null, "id");
        
        // 格式化数据
        foreach ($columnInfos as $item) {
            if (empty($lists[$item['teacher_uid']])) {
                continue;
            }
            if (empty($subjectInfos[$item['subject_id']])) {
                continue;
            }
            $teacher = $lists[$item['teacher_uid']];
            $subject = $subjectInfos[$item['subject_id']];
            if (empty($subjectParentInfos[$subject['parent_id']])) {
                continue;
            }
            $subjectParentInfo = $subjectParentInfos[$subject['parent_id']];

            if (!isset($options[$teacher['uid']])) {
                $options[$teacher['uid']] = [
                    'label' => $teacher['nickname'],
                    'value' => $teacher['uid'],
                    "children" => array(),
                ];
            }
            // 父节点
            if (empty($options[$teacher['uid']]['children'][$subjectParentInfo['id']])) {
                $options[$teacher['uid']]['children'][$subjectParentInfo['id']] = array(
                    'label' => $subjectParentInfo['name'],
                    'value' => $subjectParentInfo['id'],
                    "children" => array(),
                );
            }
            $options[$teacher['uid']]['children'][$subjectParentInfo['id']]['children'][] = array(
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