<?php

class Service_Page_Mock_Exam_Delete extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $id = empty($this->request['id']) ? 0 : intval($this->request['id']);
        if ($id <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 入参不全");
        }

        $serviceData = new Service_Data_Tag();
        $tagInfo = $serviceData->getTagById($id);
        if (empty($tagInfo)) {
            throw new Zy_Core_Exception(405, "操作失败, 标签不存在,无法删除");
        }

        $subTag = $serviceData->getTagByParentID($id);
        if (!empty($subTag)) {
            throw new Zy_Core_Exception(405, "操作失败, 无法删除有子节点的标签, 因为需要关联删除题库数据, 必须要把子标签删除干净");
        }

        $ret = $serviceData->deleteTag($id);
        if ($ret == false) {
            throw new Zy_Core_Exception(405, "删除错误, 请重试");
        }
        return array();
    }
}