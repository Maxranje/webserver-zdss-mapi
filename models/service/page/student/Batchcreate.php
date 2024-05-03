<?php

class Service_Page_Student_Batchcreate extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin() || $this->checkPartner()) {
            throw new Zy_Core_Exception(405, "无权限操作");
        }

        if (empty($this->request['excel'])) {
            throw new Zy_Core_Exception(405, "操作失败, 没有上传文件或无法解析, 请重试");
        }

        $serviceBirthplace = new Service_Data_Birthplace();
        $birthplace = $serviceBirthplace->getListByConds(array());
        if (empty($birthplace)) {
            throw new Zy_Core_Exception(405, "操作失败, 系统没有生源地配置, 请联系超管操作");
        }
        $birthplace = Zy_Helper_Utils::arrayInt($birthplace, "id");

        $serviceData = new Service_Data_Profile();

        $count = 0;
        foreach ($this->request['excel'] as $record) {
            if (empty($record['name'])
                || empty($record['phone'])
                || empty($record['nickname'])) {
                continue;
            }

            if (!is_numeric($record['phone']) 
                || strlen($record['phone']) < 6
                || strlen($record['phone']) > 12) {
                continue;
            }

            if (empty($record['birthplace']) || !in_array($record['birthplace'], $birthplace)) {
                continue;
            }

            $userInfo = $serviceData->getStudentInfoByPhone($record['phone']);
            if (!empty($userInfo)) {
                continue;
            }

            if (empty($record['school'])) {
                $record['school'] = "";
            }

            if (empty($record['graduate'])) {
                $record['graduate'] = "";
            }

            if (empty($record['sex'])) {
                $record['sex'] = "M";
            }

            $profile = [
                "type"          => Service_Data_Profile::USER_TYPE_STUDENT , 
                "name"          => $record['name'] ,
                "nickname"      => $record['nickname'] , 
                "phone"         => $record['phone'], 
                "passport"      => $record['phone'],
                "avatar"        => "",
                "state"         => Service_Data_Profile::STUDENT_ABLE,
                "school"        => $record['school']  , 
                "birthplace"    => $record['birthplace'],
                "graduate"      => $record['graduate']  ,
                "sex"           => $record['sex'] , 
                "balance"       => 0,
                "create_time"   => time() , 
                "update_time"   => time() , 
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