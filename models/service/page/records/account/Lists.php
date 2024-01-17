<?php

class Service_Page_Records_Account_Lists extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $pn         = empty($this->request['page']) ? 0 : intval($this->request['page']);
        $rn         = empty($this->request['perPage']) ? 0 : intval($this->request['perPage']);
        $nickname   = empty($this->request['nickname']) ? "" : trim($this->request['nickname']);
        $dataRange  = empty($this->request['daterangee']) ? array() : explode(",", $this->request['daterangee']);
        $isExport   = empty($this->request['is_export']) ? false : true;

        $pn = ($pn-1) * $rn;

        $conds = array();
        if (!empty($nickname)) {
            $serviceData = new Service_Data_Profile();
            $uids = $serviceData->getUserInfoLikeName($nickname);
            if (empty($uids)) {
                return array();
            }
            $uids = Zy_Helper_Utils::arrayInt($uids, "uid");
            $conds[] = sprintf("uid in (%s)", implode(",", $uids));
        }

        if (!empty($dataRange)) {
            $conds[] = sprintf("create_time >= %d", $dataRange[0]);
            $conds[] = sprintf("create_time <= %d", ($dataRange[1] + 1));
        }

        $arrAppends[] = 'order by id desc';
        if (!$isExport) {
            $arrAppends[] = "limit {$pn} , {$rn}";
        }

        $serviceData = new Service_Data_Capital();
        $lists = $serviceData->getListByConds($conds, false, NULL, $arrAppends);
        $lists = $this->formatBase($lists);

        if ($isExport) {
            $data = $this->formatExcel($lists);
            Zy_Helper_Utils::exportExcelSimple("account", $data['title'], $data['lists']);
        }
        if (empty($lists)) {
            return array();
        }

        $total = $serviceData->getTotalByConds($conds);
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
        $uids = Zy_Helper_Utils::arrayInt($lists, 'uid');

        $uids = array_unique(array_merge($uids, $operator));

        $serviceUsers = new Service_Data_Profile();
        $userInfos = $serviceUsers->getUserInfoByUids($uids);
        $userInfos = array_column($userInfos, null, "uid");

        foreach ($lists as &$item) {
            if (empty($userInfos[$item['uid']]['nickname'])) {
                continue;
            }

            $ext = empty($item['ext']) ? array() : json_decode($item['ext'], true);

            $item['type']           = $item['type'] == Service_Data_Profile::RECHARGE ? "充值" : "退费";
            $item['nickname']       = $userInfos[$item['uid']]['nickname'];
            $item['operator']       = empty($userInfos[$item['operator']]['nickname']) ? "" :$userInfos[$item['operator']]['nickname'];
            $item['create_time']    = date("Y年m月d日 H:i:s", $item['create_time']);
            $item['update_time']    = date("Y年m月d日 H:i:s", $item['update_time']);
            $item['capital']        = sprintf("%.2f元", $item['capital'] / 100);
            $item['remark']         = empty($ext['remark']) ? "" : $ext['remark'];
        }
        return $lists;
    }

    private function formatExcel($lists) {
        $result = array(
            'title' => array('日期', 'UID', '用户名', '用户类型', '金额(元)',  '备注', '操作员', "更新日期"),
            'lists' => array(),
        );
        if (empty($lists)) {
            return $result;
        }
        
        foreach ($lists as $item) {
            $tmp = array(
                $item['update_time'],
                $item['uid'],
                $item['nickname'],
                $item['type'],
                $item['capital'],
                $item['remark'],
                $item['operator'],
                $item['update_time'],
            );
            $result['lists'][] = $tmp;
        }
        return $result;

    }
}