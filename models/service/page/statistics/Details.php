<?php

class Service_Page_Statistics_Details extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $pn = empty($this->request['page']) ? 0 : intval($this->request['page']);
        $rn = empty($this->request['perPage']) ? 0 : intval($this->request['perPage']);
        $pn = ($pn-1) * $rn;

        if (!empty($this->request['export'])) {
            $pn = $rn = 0;
        }

        $studentName = empty($this->request['student_name']) ? "" : strval($this->request['student_name']);
        $groupName = empty($this->request['group_name']) ? "" : strval($this->request['group_name']);

        $serviceData = new Service_Data_Statistics();
        $lists = $serviceData->getDetailsList($studentName, $groupName, $pn, $rn );
        $total = $serviceData->getDetailsTotal($studentName, $groupName);

        if (!empty($this->request['export'])) {
            $data = $this->formatExcel($lists);
            Zy_Helper_Utils::exportExcelSimple("Statistics2", $data['title'], $data['lists']);
        }

        return array(
            'rows' => $lists,
            'total' => $total,
        );
    }

    private function formatExcel($lists) {
        $result = array(
            'title' => array('UID', 'GID', '学生', '班级', "生源地", '班级客单价', '学生客单价', '存额', '剩余/总课时', '区域管理', '结转金额'),
            'lists' => array(),
        );
        
        foreach ($lists as $item) {
            if (empty($item['uid'])) {
                continue;
            }
            $tmp = array(
                $item['uid'],
                $item['group_id'],
                $item['student_name'],
                $item['group_name'],
                $item['birthplace'],
                $item['group_price'],
                $item['student_price'],
                $item['capital'],
                $item['duration_scale'],
                $item['area_op'],
                $item['expenses'],
            );
            $result['lists'][] = $tmp;
        }
        return $result;

    }
}