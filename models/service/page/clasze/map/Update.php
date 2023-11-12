<?php

class Service_Page_Clasze_Map_Update extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $id         = empty($this->request['id']) ? 0 : intval($this->request['id']);
        $cid        = empty($this->request['cid']) ? 0 : intval($this->request['cid']);
        $bpid       = empty($this->request['bpid']) ? 0 : intval($this->request['bpid']);
        $subjectId  = empty($this->request['subject_id']) ? 0 : intval($this->request['subject_id']);
        $price      = empty($this->request['price_info']) ? 0 : intval($this->request['price_info'] * 100);

        if ($id <= 0 || $bpid <= 0 || $subjectId <= 0 || $cid <= 0 || $price <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 缺少必填参数");
        }

        // 判断是否已经有修改过的, 如果有提示那些已经修改了
        $serviceData = new Service_Data_Claszemap();
        $conds = array(
            'bpid' => $bpid,
            "subject_id" => $subjectId,
            "cid" => $cid,
        );
        $item = $serviceData->getRecordByConds($conds);
        if (!empty($item) && $item['id'] != $id) {
            throw new Zy_Core_Exception(405, "创建失败, 班型关联关系已经配置了");
        }

        $profile = [
            "cid"           => $cid, 
            "price"         => $price,
            "bpid"          => $bpid,
            "subject_id"    => $subjectId,
            "update_time"   => time(),
        ];

        $ret = $serviceData->update($id, $profile);
        if ($ret == false) {
            throw new Zy_Core_Exception(405, "更新失败, 请重试");
        }
        return array();
    }
}