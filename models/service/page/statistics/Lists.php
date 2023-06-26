<?php

class Service_Page_Statistics_Lists extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $pn         = empty($this->request['page']) ? 0 : intval($this->request['page']);
        $rn         = empty($this->request['perPage']) ? 0 : intval($this->request['perPage']);
        $uid        = empty($this->request['uid']) ? 0 : intval($this->request['uid']);
        $category   = empty($this->request['category']) ? 0 : intval($this->request['category']);
        $dataRange  = empty($this->request['daterangee']) ? array() : explode(",", $this->request['daterangee']);

        $pn = ($pn-1) * $rn;
        
        $serviceStatic = new Service_Data_Statistics();

        $conds = array();
        if ($uid > 0) {
            $conds['uid'] = $uid;
        }
        if ($category > 0) {
            $conds['category'] = $category;
        }
        if (!empty($dataRange)) {
            $conds[] = "create_time >= ". $dataRange[0];
            $conds[] = "create_time <= ". ($dataRange[1] + 1);
        }

        $arrAppends[] = 'order by id desc';
        if (empty($this->request['export'])) {
            $arrAppends[] = "limit {$pn} , {$rn}";
        }

        $lists = $serviceStatic->getListByConds($conds, false, NULL, $arrAppends);
        $total = $serviceStatic->getTotalByConds($conds);

        $lists = $this->formatBase($lists);

        if (!empty($this->request['export'])) {
            $data = $this->formatExcel($lists);
            Zy_Helper_Utils::exportExcelSimple("Statistics", $data['title'], $data['lists']);
        }

        return array(
            'rows' => $lists,
            'total' => $total,
        );
    }

    private function formatBase($lists) {

        if (empty($lists)) {
            return array();
        }
        $uids = array();
        foreach ($lists as $item) {
            $uids[intval($item['uid'])] = intval($item['uid']);
            $uids[intval($item['operator'])] = intval($item['operator']);
        }
        $uids = array_values($uids);

        $serviceUsers = new Service_Data_User_Profile();
        $userInfos = $serviceUsers->getListByConds(array('uid in ('.implode(",", $uids).')'));
        $userInfos = array_column($userInfos, null, "uid");

        foreach ($lists as &$item) {
            if (isset($userInfos[$item['uid']])) {
                $item['name'] = $userInfos[$item['uid']]['nickname'];
                $item['birthplace'] = $userInfos[$item['uid']]['birthplace'];
                $item['typeInfo'] = $userInfos[$item['uid']]['type'] == Service_Data_User_Profile::USER_TYPE_STUDENT ? "学生" : "教师";
            } else {
                $item['name'] = "未知(已被删)";
                $item['birthplace'] = "";
                $item['typeInfo'] = "未知";
            }
            if (isset($userInfos[$item['operator']])) {
                $item['operatorName'] = $userInfos[$item['operator']]['nickname'];
            }
            if ($item['category'] == Service_Data_Schedule::CATEGORY_TEACHER_RECHARGE) {
                $item['categoryInfo'] = "教师充值";
            } else if ($item['category'] == Service_Data_Schedule::CATEGORY_TEACHER_PAID) {
                $item['categoryInfo'] = "教师收入";
            } else if ($item['category'] == Service_Data_Schedule::CATEGORY_STUDENT_PAID) {
                $item['categoryInfo'] = "学生消耗(班级定价)";
            } else if ($item['category'] == Service_Data_Schedule::CATEGORY_STUDENT_RECHARGE) {
                $item['categoryInfo'] = "学生充值";
            } else if ($item['category'] == Service_Data_Schedule::CATEGORY_STUDENT_PAID_PERSONAL) {
                $item['categoryInfo'] = "学生消耗(个人定价)";
            } else if ($item['category'] == Service_Data_Schedule::CATEGORY_TEACHER_MUILT_PAID) {
                $item['categoryInfo'] = "教师收入(超阈值定价)";
            }
            $item['capitalInfo']  = ($item['capital'] / 100) . "元";
            $item['create_time'] = date('Y年m月d日 H:i:s', $item['create_time']);
            $item['update_time'] = date('Y年m月d日 H:i:s', $item['update_time']);
        }
        return $lists;
    }

    private function formatExcel($lists) {
        $result = array(
            'title' => array('UID', '用户名', '用户类型', "生源地", '场景', '金额', '备注', '操作员', '创建时间'),
            'lists' => array(),
        );
        
        foreach ($lists as $item) {
            if (empty($item['name'])) {
                continue;
            }
            $tmp = array(
                $item['uid'],
                $item['name'],
                $item['typeInfo'],
                $item['birthplace'],
                $item['categoryInfo'],
                $item['capitalInfo'],
                $item['capital_remark'],
                $item['operatorName'],
                $item['create_time'],
            );
            $result['lists'][] = $tmp;
        }
        return $result;

    }
}