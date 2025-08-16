<?php

class Service_Page_Mock_Paper_Lists extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限操作");
        }

        return array(
            "lists" => array(
                array(
                    "pid" => 1001001,
                    "bg_img" => "/public/mis/img/paper/paper" . mt_rand(1,6) . ".png" ,
                    "title" => "河北2025年中考英语试题",
                    "sub_title" => "已考20轮",
                    "premeta_id" => 10012,
                    "is_group" => 1,
                    "type" => 1, 
                    "level" => 2,
                    "tags" => array(
                        array(
                            "id" => 1,
                            "value" => "英语语法判断逻辑",
                        ),
                        array(
                            "id" => 2,
                            "value" => "英语语法判断标签",
                        ),
                        array(
                            "id" => 3,
                            "value" => "英语语法判断标签",
                        ),                        
                    ),
                    "score" => 20,
                    "frequency" => 10,
                    "operator" => "maxranje",
                    "create_time" => date("Y-m-d H:i:s"),
                    "update_time" => date("Y-m-d H:i:s"),
                ),
            ),
            "total" => 1,
        );
    }
}