<?php

class Service_Page_Mock_Tag_Update extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $id             = empty($this->request['id']) ? 0 : intval($this->request['id']);
        $title          = empty($this->request['title']) ? "" : trim($this->request['title']);
        $description    = empty($this->request['description']) ? "" : trim($this->request['description']);

        if (empty($title) || $id <= 0) {
            throw new Zy_Core_Exception(405, "操作失败, 修改的标签信息缺失");
        }

        if (!empty($description) && mb_strlen($description) > 2000) {
            throw new Zy_Core_Exception(405, "操作失败, 描述在300个字符内");
        }

        $serviceData = new Service_Data_Tag();
        
        $tagInfo = $serviceData->getTagById($id);
        if (empty($tagInfo)) {
            throw new Zy_Core_Exception(405, "操作失败, 标签不存在或已被删除,无法修改");
        }        
        $tagInfo = $serviceData->getTagByTitle($title);
        if (!empty($tagInfo) && $tagInfo["id"] != $id) {
            throw new Zy_Core_Exception(405, "操作失败, 标签名已经存在, 不能重复");
        }

        $profile = [
            "title"          => $title ,
            "description"    => $description, 
            "update_time"   => time() , 
        ];

        $ret = $serviceData->updateTag($id, $profile);
        if ($ret == false) {
            throw new Zy_Core_Exception(405, "更新失败, 请重试");
        }
        return array();
    }
}