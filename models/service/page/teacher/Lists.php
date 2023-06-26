<?php

class Service_Page_Teacher_Lists extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $pn = empty($this->request['page']) ? 1 : intval($this->request['page']);
        $rn = empty($this->request['perPage']) ? 20 : intval($this->request['perPage']);

        $pn = ($pn-1) * $rn;
        $nickname       = empty($this->request['nickname']) ? "" : $this->request['nickname'];
        $name           = empty($this->request['name']) ? "" : $this->request['name'];
        $phone          = empty($this->request['phone']) ? "" : $this->request['phone'];
        $state          = empty($this->request['state']) ? 0 : intval($this->request['state']);
        $isSelect       = empty($this->request['isSelect']) ? false : true;
        $isNoSubject    = empty($this->request['isNoSubject']) ? false : true;

        $conds = array(
            'type' => Service_Data_User_Profile::USER_TYPE_TEACHER,
        );

        if (!empty($name)) {
            $conds[] = "name like '%".$name."%'";
        }

        if (!empty($nickname)) {
            $conds[] = "nickname like '%".$nickname."%'";
        }

        if (!empty($phone)) {
            $conds[] = "phone = '".$phone."'";
        }
        
        if (!empty($state)) {
            $conds[] = "state = " . ($state == 1 ? 0 : 1);
        }

        $serviceData = new Service_Data_User_Profile();

        $arrAppends[] = 'order by uid desc';

        if (!$isSelect) {
            $arrAppends[] = "limit {$pn} , {$rn}";
        }   

        $lists = $serviceData->getListByConds($conds, false, NULL, $arrAppends);
        if ($isSelect && !$isNoSubject) {
            return $this->formatSchedule($lists);
        }
        if ($isSelect && $isNoSubject) {
            return $this->formatSelect($lists);
        }

        $total = $serviceData->getTotalByConds($conds);
        return array(
            'rows' => $lists,
            'total' => $total,
        );
        
    }

    private function formatSchedule($lists) {
        if (empty($lists)) {
            return array();
        }

        $options = array();
        $uids = array();
        foreach ($lists as $item) {
            $uids[intval($item['uid'])] = intval($item['uid']);
        }
        $uids = array_values($uids);

        // 教师露出
        $lists = array_column($lists, null , "uid");

        // 查询所有的绑定
        $serviceData = new Service_Data_Column();
        $columnInfos = $serviceData->getListByConds(array(sprintf('teacher_id in (%s)', implode(',', $uids))));
        if (empty($columnInfos)) {
            return array();
        }
        $subjectIds = array();
        foreach ($columnInfos as $item) {
            $subjectIds[intval($item['subject_id'])] = intval($item['subject_id']);
        }
        $subjectIds = array_values($subjectIds);

        # 查到所有科目名称
        $servicSubject = new Service_Data_Subject();
        $subjectInfos = $servicSubject->getListByConds(array(sprintf("id in (%s)", implode(",", $subjectIds))));
        $subjectInfos = array_column($subjectInfos, null, "id");
        
        // 格式化数据
        foreach ($columnInfos as $item) {
            $tInfo = empty($lists[$item['teacher_id']]) ? array() : $lists[$item['teacher_id']];
            $sInfo = empty($subjectInfos[$item['subject_id']]) ? array() : $subjectInfos[$item['subject_id']];

            if (empty($options[$tInfo['uid']])) {
                $options[$tInfo['uid']] = [
                    'label' => $tInfo['nickname'],
                    'value' => $tInfo['uid'],
                    "children" => array(),
                ];
            }
            if (!empty($subjectInfos[$item['subject_id']])) {
                $options[$tInfo['uid']]['children'][] = array(
                    'label' => $sInfo['name'],
                    'value' => $sInfo['id'] . "_" . $tInfo['uid'],
                );
            }
        }
        return array_values($options);
    }

    private function formatSelect($lists) {
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
}