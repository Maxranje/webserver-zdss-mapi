<?php

class Actions_Home extends Zy_Core_Actions {

    // 执行入口
    public function execute() {
        if (!$this->isLogin() ) {
            $this->redirectLogin();
        }

        try{
            // 根据业务调整请求
            if(empty($this->_request['page'])) {
                $this->_request['page'] = "calendar";
            }
            if (!in_array($this->_request["page"], array("calendar", "abroadplan"))) {
                throw new Exception("not found");
            }

            // get user info
            $serviceData = new Service_Data_Profile();
            $userInfo = $serviceData->getUserInfoByUid(intval($this->_userid));
            if (empty($userInfo)) {
                $this->redirectLogin();
            }

            // user
            $this->_output['data']["user"] = array(
                "nickname"  => $userInfo["nickname"],
                "school" => empty($userInfo['school']) ? "-" : $userInfo['school'],
                "graduate" => empty($userInfo['graduate']) ? "-" : $userInfo['graduate'],
                "sex" => $userInfo["sex"] == "M" ? "man" : "female",
                "type" => $userInfo["type"],
            );

            // 日历
            if ($this->_request["page"] == "calendar") {
                $serivce = new Service_Page_Api_Page ($this->_request, $this->_userInfo);
                $this->_output['data']["lists"] = $serivce->execute();
                $this->displayTemplate("client");
            }

            // 留学计划
            if ($this->_request["page"] == "abroadplan") {
                $serivce = new Service_Page_Api_Apconfirm ($this->_request, $this->_userInfo);
                $this->_output['data']['apackage'] = $serivce->execute();
                $this->displayTemplate("abroadplan");
            }

        } catch (Exception $e) {
            $this->redirect404();
        }
    }
}