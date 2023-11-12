<?php

class Service_Page_Clasze_Map_Lists extends Zy_Core_Service{

    private $bpid = 0;

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $pn         = empty($this->request['page']) ? 1 : intval($this->request['page']);
        $rn         = empty($this->request['perPage']) ? 20 : intval($this->request['perPage']);
        $cid        = empty($this->request['cid']) ? 0 : intval($this->request['cid']);
        $bpid       = empty($this->request['bpid']) ? 0 : intval($this->request['bpid']);
        $subjectId  = empty($this->request['subject_id']) ? 0 : intval($this->request['subject_id']);

        $pn = ($pn-1) * $rn;

        $conds = array();
        if ($cid > 0) {
            $conds[] = sprintf("cid = %d", $cid);
        }
        if ($bpid > 0) {
            $conds[] = sprintf("bpid = %d", $bpid);
        }
        if ($subjectId > 0) {
            $conds[] = sprintf("subject_id = %d", $subjectId);
        }
        
        $serviceData = new Service_Data_Claszemap();

        $arrAppends[] = 'order by bpid, subject_id';
        $arrAppends[] = "limit {$pn} , {$rn}";
        
        $lists = $serviceData->getListByConds($conds, array(), NULL, $arrAppends);
        if (empty($lists)) {
            return array();
        }

        $lists = $this->formatBase($lists);
        $total = $serviceData->getTotalByConds($conds);

        return array(
            'lists' => $lists,
            'total' => $total,
        );
    }

    public function formatBase($lists) {
        if (empty($lists)) {
            return array();
        }

        $cids = Zy_Helper_Utils::arrayInt($lists, "cid");
        $bpids = Zy_Helper_Utils::arrayInt($lists, "bpid");
        $subjectIds = Zy_Helper_Utils::arrayInt($lists, "subject_id");

        $serviceData = new Service_Data_Clasze();
        $cInfos = $serviceData->getClaszeByIds($cids);
        $cInfos = array_column($cInfos, null, "id");

        $serviceData = new Service_Data_Birthplace();
        $bInfos = $serviceData->getBirthplaceByIds($bpids);
        $bInfos = array_column($bInfos, null, "id");

        $serviceData = new Service_Data_Subject();
        $sInfos = $serviceData->getSubjectByIds($subjectIds);
        $sInfos = array_column($sInfos, null, "id");

        $result = array();
        foreach ($lists as $item) {
            if (empty($bInfos[$item['bpid']]['name'])) {
                continue;
            }
            if (empty($sInfos[$item['subject_id']]['name'])) {
                continue;
            }
            if (empty($cInfos[$item['cid']]['name'])) {
                continue;
            }
            $tmp = $item;
            $tmp['clasze_name']     = $cInfos[$item['cid']]['name'];
            $tmp['birthplace']      = $bInfos[$item['bpid']]['name'];
            $tmp['subject_name']    = $sInfos[$item['subject_id']]['name'];
            $tmp['price_info']      = sprintf("%.2f", $item['price'] / 100);
            $tmp['create_time']     = date("Y-m-d H:i:s", $item['create_time']);
            $tmp['update_time']     = date("Y-m-d H:i:s", $item['update_time']);

            $result[] = $tmp;
        }
        return $result;
    }
}