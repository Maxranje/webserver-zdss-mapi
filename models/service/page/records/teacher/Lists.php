<?php

class Service_Page_Records_Teacher_Lists extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $pn         = empty($this->request['page']) ? 0 : intval($this->request['page']);
        $rn         = empty($this->request['perPage']) ? 0 : intval($this->request['perPage']);
        $nickname   = empty($this->request['nickname']) ? "" : trim($this->request['nickname']);
        $isExport   = empty($this->request['is_export']) ? false : true;

        $pn = ($pn-1) * $rn;

        if (!empty($nickname) && (mb_strlen($nickname) > 10 || !Zy_Helper_Utils::checkStr($nickname))) {
            throw new Zy_Core_Exception(405, "操作失败, 输入存在非法字符或长度超过10");
        }

        $serviceData = new Service_Data_Profile();

        $conds = array(
            "type" => Service_Data_Profile::USER_TYPE_TEACHER,
        );
        if (!empty($nickname)) {
            $conds[] = "nickname like '%".$nickname."%'";
        }

        $arrAppends[] = 'order by uid desc';
        if (!$isExport) {
            $arrAppends[] = "limit {$pn} , {$rn}";
        }
        
        $lists = $serviceData->getListByConds($conds, array("uid", "nickname"), null, $arrAppends);
        if (empty($lists)) {
            return array();
        }

        $lists = $this->formatBase($lists);
        if ($isExport) {
            $data = $this->formatExcel($lists);
            Zy_Helper_Utils::exportExcelSimple("teacherduration", $data['title'], $data['lists']);
        }
        $total = $serviceData->getTotalByConds($conds);

        return array(
            'rows' => $lists,
            'total' => $total,
        );
    }

    private function formatBase($lists) {
        foreach ($lists as &$item) {
            $item['lm_duration'] = 0;
            $item['nm_duration'] = 0;
            $item['cm_duration'] = 0;
        }
        $lists = array_column($lists, null, "uid");

        $serviceData = new Service_Data_Schedule();

        // 上个月
        $sts = strtotime(date('Y-m-d', strtotime(date('Y-m-01') . ' -1 month')));
        $ets = strtotime(date('Y-m-1'));
        $conds = array(
            sprintf("start_time >= %d", $sts),
            sprintf("end_time <= %d", $ets),
        );
        $schedules = $serviceData->getListByConds($conds, array("teacher_uid", "start_time", "end_time"));

        if (!empty($schedules)) {
            foreach ($schedules as $item) {
                if (isset($lists[$item['teacher_uid']])) {
                    $lists[$item['teacher_uid']]['lm_duration']+= $item['end_time'] - $item['start_time'];
                }
            }
        }

        // 下个月
        $sts = strtotime(date('Y-m-d', strtotime(date('Y-m-01') . ' +1 month')));
        $ets = strtotime(date('Y-m-d', strtotime(date('Y-m-01') . ' +2 month')));
        $conds = array(
            sprintf("start_time >= %d", $sts),
            sprintf("end_time <= %d", $ets),
        );
        $schedules = $serviceData->getListByConds($conds, array("teacher_uid", "start_time", "end_time"));

        if (!empty($schedules)) {
            foreach ($schedules as $item) {
                if (isset($lists[$item['teacher_uid']])) {
                    $lists[$item['teacher_uid']]['nm_duration']+= $item['end_time'] - $item['start_time'];
                }
            }
        }

        // 本月
        $sts = strtotime(date("Y-m-1"));
        $ets = strtotime(date('Y-m-d', strtotime('first day of next month')));
        $conds = array(
            sprintf("start_time >= %d", $sts),
            sprintf("end_time <= %d", $ets),
        );
        $schedules = $serviceData->getListByConds($conds, array("teacher_uid", "start_time", "end_time"));

        if (!empty($schedules)) {
            foreach ($schedules as $item) {
                if (isset($lists[$item['teacher_uid']])) {
                    $lists[$item['teacher_uid']]['cm_duration']+= $item['end_time'] - $item['start_time'];
                }
            }
        }

        $now = date("Y年m月d日");
        foreach ($lists as &$item) {
            $item['create_time'] = $now;
            $item['cm_duration'] = sprintf("%.2f", $item['cm_duration'] / 3600);
            $item['lm_duration'] = sprintf("%.2f", $item['lm_duration'] / 3600);
            $item['nm_duration'] = sprintf("%.2f", $item['nm_duration'] / 3600);
        }


        return array_values($lists);
    }

    private function formatExcel($lists) {
        $result = array(
            'title' => array('数据日期', '教师名', 'UID', '上个月课时' , '本月课时', '下个月课时'),
            'lists' => array(),
        );
        if (empty($lists)) {
            return $result;
        }
        
        foreach ($lists as $item) {
            $tmp = array(
                $item['create_time'],
                $item['nickname'],
                $item['uid'],
                $item['lm_duration'],
                $item['cm_duration'],
                $item['nm_duration'],
            );
            $result['lists'][] = $tmp;
        }
        return $result;

    }
}   