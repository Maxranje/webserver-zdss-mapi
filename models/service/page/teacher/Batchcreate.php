<?php

class Service_Page_Teacher_Create extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        if (empty($this->request['excel'])) {
            throw new Zy_Core_Exception(405, "上传文件解析失败, 请检查");
        }

        $serviceData = new Service_Data_User_Profile();

        $count = 0;
        foreach ($this->request['excel'] as $record) {
            if (empty($record['name'])
                || empty($record['phone'])) {
                continue;
            }

            $userInfo = $serviceData->getUserInfo($record['name'], $record['phone']);
            if (!empty($userInfo)) {
                continue;
            }

            if (empty($record['sex'])) {
                $record['sex'] = "M";
            }

            if (empty($record['capital'])) {
                $record['capital'] = 0;
            }

            $profile = [
                "type"  => Service_Data_User_Profile::USER_TYPE_TEACHER , 
                "name"  => $record['name'] , 
                "phone"  => $record['phone']  , 
                "avatar" => "",
                "sex"  => $record['sex'] , 
                "teacher_capital" => $record['capital'],
                "create_time" => time(),
                "update_time" => time(),
            ];

            $ret = $serviceData->createUserInfo($profile);
            if ($ret == false) {
                continue;
            }

            $count++;
        }

        return array("count" => $count);
    }
}