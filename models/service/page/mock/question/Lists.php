<?php

class Service_Page_Mock_Question_Lists extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限操作");
        }

        $pn             = empty($this->request['page']) ? 1 : intval($this->request['page']);
        $rn             = empty($this->request['perPage']) ? 20 : intval($this->request['perPage']);
        $qids           = empty($this->request['qids']) ? array() : Zy_Helper_Utils::arrayInt(explode(",", $this->request['qids']));
        $pid            = empty($this->request['pid']) ? 0 : intval($this->request['pid']);
        $type           = empty($this->request['type']) ? 0 : intval($this->request['type']);
        $level          = empty($this->request['level']) ? 0 : intval($this->request['level']); 
        $parentId       = empty($this->request['parent_id']) ? 0 : intval($this->request['parent_id']); 
        $description    = empty($this->request['description']) ? "" : trim($this->request['description']);
        $tagIds         = empty($this->request['tag_ids']) ? array() : Zy_Helper_Utils::arrayInt(explode(",", $this->request['tag_ids']));
        $sourceIds      = empty($this->request['source_ids']) ? array() : Zy_Helper_Utils::arrayInt(explode(",", $this->request['source_ids']));
        $isSelect       = empty($this->request['is_select']) ? false : true;
        $pn             = ($pn-1) * $rn;        

        $conds = array(
            "is_coll" => 0, // 不取壳子
        );

        // 根据tag找qid
        if (count($tagIds) > 0) {
            $serviceData = new Service_Data_Questiontag();
            $questionIds = $serviceData->getQidByTagids($tagIds);
            if (!empty($qids)) {
                $qids = array_intersect($questionIds, $qids);
            } else {
                $qids = $questionIds;
            }
            if (empty($qids)) {
                return array();
            }            
        }

        // 根据source找qid
        if (count($sourceIds) > 0) {
            $serviceData = new Service_Data_QuestionSource();
            $questionIds = $serviceData->getQidBySoureIds($sourceIds);
            if (!empty($qids)) {
                $qids = array_intersect($questionIds, $qids);
            } else {
                $qids = $questionIds;
            }
            if (empty($qids)) {
                return array();
            }     
        }

        if (count($qids) > 0) {
            $conds[] = sprintf("qid in (%s)", implode(",", $qids));
        }
        if (in_array($type, Service_Data_Question::QUESTION_TYPE_MAP)) {
            $conds[] = sprintf("type = %d", $type);
        }
        if (in_array($level, Service_Data_Question::QUESTION_LEVEL_MAP)) {
            $conds[] = sprintf("level = %d", $level);
        }
        if (!empty($description)) {
            $conds[] = "description like '%".$description."%'";
        }
        if ($parentId > 0) {
            $conds = array("parent_id" => $parentId);
        }

        $arrAppends = array(
            'order by update_time desc',
        );
        if (!$isSelect) {
            $arrAppends[] = "limit {$pn} , {$rn}";
        }

        $serviceQuestion = new Service_Data_Question();
        $lists = $serviceQuestion->getListByConds($conds, array(), null, $arrAppends);
        if (empty($lists)) {
            return array();
        }
        $lists = $this->formatBase($lists);
        if ($isSelect) {
            return $this->formatSelect($lists, $pid);
        }
        $total = $serviceQuestion->getTotalByConds($conds);
        return array(
            'rows' => $lists,
            'total' => $total,
        );
    }

    // 格式化
    private function formatBase ($lists) {
        $uids = Zy_Helper_Utils::arrayInt($lists, "operator");
        $questionQids = Zy_Helper_Utils::arrayInt($lists, "qid");

        // 获取管理员
        $serviceData = new Service_Data_Profile();
        $userInfos = $serviceData->getUserInfoByUids($uids);
        $userInfos = array_column($userInfos, null, "uid");

        // qid
        $serviceData = new Service_Data_Questiontag();
        $qtMap = $serviceData->getTagidsByQids($questionQids);
        $tagInfos = $tagIds = array();
        foreach ($qtMap as $v) {
            $tagIds = array_merge($tagIds, $v);
        }
        $tagIds = Zy_Helper_Utils::arrayInt($tagIds);
        if (!empty($tagIds)) {
            $serviceData = new Service_Data_Tag();
            $tagInfos = $serviceData->getTagByIds($tagIds);
            $tagInfos = array_column($tagInfos, null, "id");
        }

        // qid
        $serviceData = new Service_Data_QuestionSource();
        $qsMap = $serviceData->getSourceByQid($questionQids);
        $sourceInfos = $sourceIds = array();
        foreach ($qsMap as $v) {
            $sourceIds = array_merge($sourceIds, $v);
        }
        $sourceIds = Zy_Helper_Utils::arrayInt($sourceIds);
        if (!empty($sourceIds)) {
            $sourceInfos = $serviceData->getSourceByIds($sourceIds);
            $sourceInfos = array_column($sourceInfos, null, "id");
        }        

        // 看看试题有没有考过试
        $serviceData = new Service_Data_Paper();
        $paperInfos = $serviceData->getPaperIdsByQids($questionQids, true);

        $result = array();
        foreach ($lists as $v) {
            $tmp = array();
            $tmp["qid"]             = $v["qid"];
            $tmp["description"]     = $v["description"];
            $tmp["type"]            = $v["type"];
            $tmp["type_info"]       = Service_Data_Question::QUESTION_TYPE_MAP_INFO[$v["type"]];
            $tmp["level"]           = $v["level"];
            $tmp["level_info"]      = Service_Data_Question::QUESTION_LEVEL_MAP_INFO[$v["level"]];
            $tmp["score"]           = $v["score"];
            $tmp["parent_id"]       = empty($v["parent_id"]) ? "-" : $v["parent_id"];
            $tmp["subject_name"]    = "-";
            $tmp["operator"]        = empty($userInfos[$v["operator"]]["nickname"]) ? "" : $userInfos[$v["operator"]]["nickname"];
            $tmp["update_time"]     = date("Y-m-d", $v["update_time"]);
            $tmp["frequency"]       = empty($paperInfos[$v['qid']]) ? 0 : intval($paperInfos[$v['qid']]);
            $tmp["tags"]            = array();
            $tmp['tag_list']        = array();
            $tmp['sources']         = array();
            $tmp['source_list']     = array();

            // 标签
            if (!empty($qtMap[$v["qid"]])) {    
                foreach ($qtMap[$v["qid"]] as $tid) { // 取tagid
                    if (empty($tagInfos[$tid]["title"])) {
                        continue;
                    }
                    $tmp["tags"][] = array(
                        "title" => $tagInfos[$tid]["title"],
                        "description" => empty($tagInfos[$tid]["description"]) ? "" : $tagInfos[$tid]["description"],
                    );  
                    $tmp['tag_list'][] = $tagInfos[$tid]["title"];
                }
            }            

            // 来源
            if (!empty($qsMap[$v["qid"]])) {    
                foreach ($qsMap[$v["qid"]] as $sid) { // 取tagid
                    if (empty($sourceInfos[$sid]["name"])) {
                        continue;
                    }
                    $tmp["sources"][] = array(
                        "name" => $sourceInfos[$sid]["name"],
                    );  
                }
            }            

            $tmp["title"] = "QuestionID " . $tmp["qid"];
            if ($tmp["parent_id"] > 0) {
                $tmp["title"] = sprintf("<span class='label label-danger mr-2'>G</span> %s", $tmp["title"]);
            }
            $result[] = $tmp;
        }
        return $result;
    }

    private function formatSelect($lists, $pid = 0) {
        $paperQids = array();
        if ($pid > 0) {
            $serviceData = new Service_Data_Paper();
            $paperQids = $serviceData->getPaperQuestionIds($pid);
        }
        
        $options = array();
        foreach ($lists as $item) {
            if ($item['parent_id'] > 0) {
                if (!isset($options[$item["parent_id"]])) {
                    $options[$item["parent_id"]] = array(
                        'label'         => sprintf("题目组: %d", $item["parent_id"]),
                        "children"      => array(),
                    );
                }
                $options[$item["parent_id"]]["children"][] = array(
                    'label' => empty($item['description']) ? strip_tags($item["content"]) : $item["description"],
                    'tag'   => sprintf("%s (%s)",  Service_Data_Question::QUESTION_TYPE_MAP_INFO[$item["type"]], Service_Data_Question::QUESTION_LEVEL_MAP_INFO[$item["level"]]),
                    'value' => $item['qid'],
                );
            } else {
                $options[] = array(
                    'label' => empty($item['description']) ? strip_tags($item["content"]) : $item["description"],
                    'tag'   => sprintf("%s (%s)",  Service_Data_Question::QUESTION_TYPE_MAP_INFO[$item["type"]], Service_Data_Question::QUESTION_LEVEL_MAP_INFO[$item["level"]]),
                    'value' => $item['qid'], 
                );
            }
        }
        return array('options' => array_values($options), "value" => implode(",",$paperQids));
    } 
}