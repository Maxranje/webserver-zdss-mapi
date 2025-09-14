<?php

class Service_Page_Mock_Tag_Create extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $id             = empty($this->request['id']) ? 0 : intval($this->request['id']);
        $title          = empty($this->request['new_title']) ? "" : trim($this->request['new_title']);
        $description    = empty($this->request['new_description']) ? "" : trim($this->request['new_description']);

        if (empty($title)) {
            throw new Zy_Core_Exception(405, "操作失败, 标签信息缺失");
        }

        if (!empty($description) && mb_strlen($description) > 2000) {
            throw new Zy_Core_Exception(405, "操作失败, 描述在2000个字符内");
        }

        $serviceData = new Service_Data_Tag();

        $tagInfo = $serviceData->getTagByTitle($title);
        if (!empty($tagInfo)) {
            throw new Zy_Core_Exception(405, "操作失败, 标签名已经存在, 不能重复");
        }

        if ($id > 0) {
            $parentInfo = $serviceData->getTagById($id);
            if (empty($parentInfo)) {
                throw new Zy_Core_Exception(405, "操作失败, 父标签不存在");
            }
        }

        $profile = [
            "title"          => $title , 
            "parent_id"      => $id, 
            "description"    => $description, 
            "create_time"   => time() , 
            "update_time"   => time() , 
        ];

        $ret = $serviceData->createTag($profile);
        if ($ret == false) {
            throw new Zy_Core_Exception(405, "创建失败, 请重试");
        }
        return array();
    }
}