<?php

class Service_Page_Records_Lists extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $pn         = empty($this->request['page']) ? 0 : intval($this->request['page']);
        $rn         = empty($this->request['perPage']) ? 0 : intval($this->request['perPage']);
        $uid        = empty($this->request['uid']) ? 0 : intval($this->request['uid']);
        $category   = empty($this->request['category']) ? 0 : intval($this->request['category']);
        $dataRange  = empty($this->request['daterangee']) ? array() : explode(",", $this->request['daterangee']);
        $isExport   = empty($this->request['is_export']) ? false : true;

        $pn = ($pn-1) * $rn;

        $conds = array();
        if ($uid > 0) {
            $conds['uid'] = $uid;
        }
        if ($category > 0) {
            $conds['category'] = $category;
        }
        if (!empty($dataRange)) {
            $conds[] = sprintf("create_time >= %d", $dataRange[0]);
            $conds[] = sprintf("create_time <= %d", ($dataRange[1] + 1));
        }

        $arrAppends[] = 'order by id desc';
        if (!$isExport) {
            $arrAppends[] = "limit {$pn} , {$rn}";
        }

        $serviceRecords = new Service_Data_Records();
        $lists = $serviceRecords->getListByConds($conds, false, NULL, $arrAppends);
        $lists = $this->formatBase($lists);

        if ($isExport) {
            $data = $this->formatExcel($lists);
            Zy_Helper_Utils::exportExcelSimple("payrecords", $data['title'], $data['lists']);
        }
        if (empty($lists)) {
            return array();
        }

        $total = $serviceRecords->getTotalByConds($conds);
        return array(
            'rows' => $lists,
            'total' => $total,
        );
    }

    private function formatBase($lists) {

        if (empty($lists)) {
            return array();
        }

        $operator = Zy_Helper_Utils::arrayInt($lists, 'operator');
        $groupIds = Zy_Helper_Utils::arrayInt($lists, 'group_id');
        $uids = Zy_Helper_Utils::arrayInt($lists, 'uid');

        $uids = array_unique(array_merge($uids, $operator));

        $serviceUsers = new Service_Data_Profile();
        $userInfos = $serviceUsers->getUserInfoByUids($uids);
        $userInfos = array_column($userInfos, null, "uid");

        $serviceGroup = new Service_Data_Group();
        $groupInfos = $serviceGroup->getListByConds(array(sprintf("id in (%s)", implode(",", $groupIds))));
        $groupInfos = array_column($groupInfos, null, "id");

        foreach ($lists as &$item) {
            if (empty($userInfos[$item['uid']]['nickname'])) {
                continue;
            }
            if (empty($userInfos[$item['operator']]['nickname'])) {
                continue;
            }
            $item['type']           = $item['type'] == Service_Data_Profile::USER_TYPE_STUDENT ? "学员" : "教师";
            $item['nickname']       = $userInfos[$item['uid']]['nickname'];
            $item['operator']       = $userInfos[$item['operator']]['nickname'];
            $item['create_time']    = date("Y年m月d日 H:i:s", $item['create_time']);
            $item['update_time']    = date("Y年m月d日 H:i:s", $item['update_time']);
            $item['group_name']     = empty($groupInfos[$item['group_id']]['name']) ? "-" : $groupInfos[$item['group_id']]['name'];
            $item['birthplace']     = empty($userInfos[$item['uid']]['birthplace']) ? "-" : $userInfos[$item['uid']]['birthplace'];
            $item['money_info']     = sprintf("%.2f元", $item['money'] / 100);
            $item['order_id']       = empty($item['order_id']) ? "-" : $item['order_id'];
        }
        return $lists;
    }

    private function formatExcel($lists) {
        $result = array(
            'title' => array('UID', '用户名', '用户类型', '状态', '场景', '金额(元)', "生源地", '班级', '订单ID', '操作员', '更新时间'),
            'lists' => array(),
        );
        if (empty($lists)) {
            return $result;
        }
        
        foreach ($lists as $item) {
            $tmp = array(
                $item['uid'],
                $item['nickname'],
                $item['type'],
                $item['state'] == Service_Data_Records::RECORDS_NOMARL ? "正常" : "撤销",
                $item['category'] == Service_Data_Schedule::CATEGORY_STUDENT_PAID ? "学员消费" : "教师收入",
                $item['money_info'],
                $item['birthplace'],
                $item['group_name'],
                $item['order_id'],
                $item['operator'],
                $item['update_time'],
            );
            $result['lists'][] = $tmp;
        }
        return $result;

    }
}