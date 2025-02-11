<?php

class Service_Page_Api_Apconfirm extends Zy_Core_Service{

    // 查询通过登录uid上课俩表, 
    public function execute () {
        if (!$this->checkStudent()) {
            throw new Exception("login type err") ;
        }

        $apckageIdMd5 = empty($this->request["apckage"]) ? "" : trim($this->request['apckage']);

        $checkId = empty($this->request["check_id"]) ? "" : trim($this->request['check_id']);
        $token = empty($this->request["token"]) ? "" : trim($this->request['token']);// 也是idMD5只不过来源不一样
        if (!empty($checkId)) {
            return $this->check($token, $checkId);
        } else {
            return $this->page($apckageIdMd5);
        }
    }

    public function check($token, $checkId){
        $uid = $this->adption["userid"];
        if (empty($token)) {
            throw new Exception("request err!") ;
        }

        list($a,$b,$id,$c,$d,$e) = explode("_", $checkId);
        if (intval($id) <= 0) {
            throw new Exception("request err") ;
        }

        $serviceData = new Service_Data_Aporderpackage();
        $apackageLists = $serviceData->getListByConds(array("uid" => $uid));
        $apackageInfo  = array(); 
        foreach ($apackageLists as $v) {
            $idMd5 = md5($v["id"]);
            if ($token == $idMd5 && in_array($v['state'], Service_Data_Aporderpackage::APORDER_STATUS_ABLE_MAP)) {
                $apackageInfo = $v;
            }
        }
        if (empty($apackageInfo)) {
            throw new Zy_Core_Exception(405, "操作失败, 服务不存在或不在有效状态内, 不可操作, 请联系学管");
        }

        $serviceData = new Service_Data_Apackageconfirm();
        $confirm= $serviceData->getConfirmById(intval($apackageInfo["id"]));
        if (empty($confirm["content"])) {
            throw new Zy_Core_Exception(405, "操作失败, 检查项不存在, 请联系学管check");
        }      
        
        $content = $confirm["content"];
        $flag = false;
        foreach ($content as &$v) {
            foreach ($v["items"] as &$vv) {
                if (!empty($vv["key"]) && "sc_" . $vv["key"] == $checkId)  {
                    $vv["is_sc"] = 1;
                    $vv['s_id'] = $uid;
                    $vv['s_time'] = time();
                    $flag = true;
                }
            }
        }

        if ($flag) {
            $profile = [
                "content"       => json_encode($content), 
                "update_time"   => time() , 
            ];
            $ret = $serviceData->update(intval($confirm['id']), $profile);
            if ($ret == false) {
                throw new Zy_Core_Exception(405, "check failed");
            }
        }
        return array();
    }

    public function page($apckageIdMd5) {
        $result = array(
            "total" => 0,
            "service" => array(),
            "lists" => array(),
            "state" => 0,
            "progress" => 0,
        );

        $uid = $this->adption["userid"];
        $serviceData = new Service_Data_Aporderpackage();
        $apackageLists = $serviceData->getListByConds(
            array(
                "uid"=>intval($uid), 
                sprintf("state in (%s)", implode(",", Service_Data_Aporderpackage::APORDER_STATUS_ABLE_MAP)),
            ), 
        array("id", "abroadplan_id"));
        if (empty($apackageLists)) {
            return $result;
        }

        $result['total'] = count($apackageLists);
        
        $abroadplanIds = Zy_Helper_Utils::arrayInt($apackageLists, "abroadplan_id");
        $serviceData = new Service_Data_Abroadplan();
        $abroadplanInfor = $serviceData->getAbroadplanByIds($abroadplanIds);
        $abroadplanInfor = array_column($abroadplanInfor, null, "id");

        $apckageId = 0;
        foreach ($apackageLists as $v) {
            $idMd5 = md5($v["id"]);
            $tmp = array(
                "url" => HOSTNAME. "mapi/dashboard/home?page=abroadplan&apckage=" . $idMd5,
                "name" => empty($abroadplanInfor[$v['abroadplan_id']]["name"]) ? "-" : $abroadplanInfor[$v['abroadplan_id']]["name"], 
            );
            if ($idMd5 == $apckageIdMd5) {
                $tmp["is_selected"] = 1;
                $apckageId = intval($v['id']);
            }
            $result['service'][] =$tmp;
        }

        if ($apckageId <= 0) {
            return $result;
        }

        $serviceData = new Service_Data_Apackageconfirm();
        $confirm = $serviceData->getConfirmById($apckageId);
        if (empty($confirm["content"])) {
            $result['state'] = 1;
            return $result;
        }

        $progressCount = $allCount = 0;
        foreach ($confirm['content'] as $i => &$v) {
            $itemCount = count($v["items"]);
            foreach ($v["items"] as $ii => $vv) {
                $allCount ++;
                if (!empty($vv['is_oc']) && !empty($vv['is_sc'])) {
                    $progressCount ++;
                    $itemCount--;
                }
            }
            if ($itemCount <= 0) {
                $v["is_checkall"] = 1;
            }
        }

        $result["lists"] = $confirm["content"];
        $result["progress"] = $allCount <= 0 ? 0 : floatval(sprintf("%.1f", $progressCount / $allCount) * 100) ;
        $result["state"] = 2;
        $result["token"] = $apckageIdMd5;
        return $result;
    }
}