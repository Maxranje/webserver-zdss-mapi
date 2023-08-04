<?php

class Service_Page_Column_Update extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $columnId   = empty($this->request['column_id']) ? 0 : intval($this->request['column_id']);
        $teacherUid = empty($this->request['teacher_uid']) ? 0 : intval($this->request['teacher_uid']);
        $subjectId  = empty($this->request['subject_id']) ? 0 : intval($this->request['subject_id']);
        $price      = empty($this->request['price']) ? array() : $this->request['price'];

        if ($columnId <=0 || $teacherUid <= 0 || $subjectId <= 0 || empty($price)) {
            throw new Zy_Core_Exception(405, "操作错误, 无法获取教师或科目或没有配置阈值客单价, 请检查");
        }

        foreach ($price as &$item) {
            if ($item['number'] <= 0 || $item["price"] <= 0) {
                throw new Zy_Core_Exception(405, "操作失败, 人数和价格存在为空情况");
            }
            $item['number'] = intval($item['number']);
            $item['price']  = intval($item['price']) * 100;
        }

        $price = array_column(array_values($price), null, 'number');
        ksort($price);

        $serviceData = new Service_Data_Column();
        $column = $serviceData->getColumnById($columnId);
        if (empty($column) ) {
            throw new Zy_Core_Exception(405, "操作失败, 绑定信息不存在, 无法修改");
        }

        $column = $serviceData->getColumnByTSId($teacherUid, $subjectId);
        if (!empty($column) && $column['id'] != $columnId) {
            throw new Zy_Core_Exception(405, "操作失败, 已经绑定无需重新绑定");
        }

        $profile = [
            "subject_id"    => $subjectId, 
            "teacher_uid"   => $teacherUid, 
            "price"         => json_encode($price), 
            'update_time'   => time(),
        ];
        $ret = $serviceData->editColumn($columnId, $profile);

        if ($ret === false) {
            throw new Zy_Core_Exception(405, "更新失败, 请重试");
        }
        return array();
    }
}