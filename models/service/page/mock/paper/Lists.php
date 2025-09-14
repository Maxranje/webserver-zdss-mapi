<?php

class Service_Page_Mock_Paper_Lists extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限操作");
        }

        $pn             = empty($this->request['page']) ? 1 : intval($this->request['page']);
        $rn             = empty($this->request['perPage']) ? 20 : intval($this->request['perPage']);
        $title          = empty($this->request['title']) ? "" : trim($this->request['title']);
        $type           = empty($this->request['type']) ? 0 : intval($this->request['type']);
        $qids           = empty($this->request['qids']) ? array() : Zy_Helper_Utils::arrayInt(explode(",", $this->request['qids']));
        $pids           = empty($this->request['pids']) ? array() : Zy_Helper_Utils::arrayInt(explode(",", $this->request['pids']));
        $sourceIds      = empty($this->request['source_ids']) ? array() : Zy_Helper_Utils::arrayInt(explode(",", $this->request['source_ids']));
        $subjectIds     = empty($this->request['subject_ids']) ? array() : Zy_Helper_Utils::arrayInt(explode(",", $this->request['subject_ids']));
        $isSelect       = empty($this->request['is_select']) ? false : true;
        $pn             = ($pn-1) * $rn;        

        $conds = array();
        // sourceid找pid
        if (count($sourceIds) > 0) {
            $serviceData = new Service_Data_Source();
            $paperIds = $serviceData->getPaperBySourceIds($sourceIds);
            if (!empty($paperIds)) {
                $pids = array_intersect($paperIds, $pids);
            }
        }

        // question找paper
        $servicePaper = new Service_Data_Paper();        
        if (count($qids) > 0) {
            $paperIds = $servicePaper->getPaperIdsByQids($qids);
            if (!empty($paperIds)) {
                $pids = array_intersect($paperIds, $pids);
            }
        }    
        if (count($pids) > 0) {
            $conds[] = sprintf("pid in (%s)", implode(",", $pids));
        }            

        // subjectid找pid
        if (count($subjectIds) > 0) {
            $conds[] = sprintf("subject_id in (%s)", implode(",", $subjectIds));
        }
        if (!empty($title)) {
            $conds[] = "title like '%" .$title. "%'";
        }
        if (in_array($type, Service_Data_Paper::PAPER_TYPE_MAP)) {
            $conds[] = sprintf("type = %d", $type);
        }
        $arrAppends = array(
            'order by update_time desc',
        );
        if (!$isSelect) {
            $arrAppends[] = "limit {$pn} , {$rn}";
        }

        $lists = $servicePaper->getListByConds($conds, array(), null, $arrAppends);
        if (empty($lists)) {
            return array();
        }
        if ($isSelect) {
            return $this->formatSelect($lists);
        }
        $lists = $this->formatBase($lists);
        $total = $servicePaper->getTotalByConds($conds);
        return array(
            'rows' => $lists,
            'total' => $total,
        );
    }

    // 格式化
    private function formatBase ($lists) { 
        $pids = Zy_Helper_Utils::arrayInt($lists, "pid");

        $serviceData = new Service_Data_Paper();
        $sourceInfos = $serviceData->getSourceByPaperIds($pids);

        $result = array();
        foreach ($lists as $v) {
            $sourceInfo = empty($sourceInfos[$v["pid"]]) ? array() :$sourceInfos[$v["pid"]];

            $tmp = array();
            $tmp["pid"]             = $v["pid"];
            $tmp["title"]           = $v["title"];
            $tmp["type"]            = $v["type"];
            $tmp["bg_img"]          = sprintf("/public/mis/img/paper/paper%d.png", ($v["pid"] % 6) + 1);
            $tmp["weight_score"]    = $v["weight_score"];
            $tmp["frequency"]       = $v["frequency"];
            $tmp["remark"]          = $v["remark"];
            $tmp["sub_title"]       = sprintf("已关联%d次模考", $v["frequency"]);
            $tmp["subject_id"]      = $v["subject_id"] <= 0 ? "" : $v["subject_id"]; 
            $tmp["source_ids"]      = Zy_Helper_Utils::arrayInt($sourceInfo, "source_id");
            $result[] = $tmp;
        }
        return $result;
    }

    private function formatSelect($lists, $qid = 0) {
        $pids = array();
        if (!$qid > 0) {
            $serviceData = new Service_Data_Paper();
            $pids = $serviceData->getPaperIdsByQids(array($qid));
        }
        
        $options = array();
        foreach ($lists as $item) {
            $options[] = array(
                'label' => $item["title"],
                'value' => $item['pid'], 
            );
        }
        return array('options' => array_values($options), "value" => implode(",",$pids));
    } 
}