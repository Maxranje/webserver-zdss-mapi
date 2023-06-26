<?php

class Service_Page_Group_Singleprice extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $groupId = empty($this->request['group_id']) ? 0 : intval($this->request['group_id']);

        if ($groupId <= 0) {
            return array();
        }

        $serviceGroup = new Service_Data_Group();
        $groupInfo = $serviceGroup->getGroupById($groupId);
        if (empty($groupInfo)) {
            return array();
        }

        $serviceGroupMap = new Service_Data_User_Group();
        $studentUids = $serviceGroupMap->getGroupMapByGid($groupId);
        if (empty($studentUids)) {
            return array();
        }

        $uids = array();
        foreach ($studentUids as $item) {
            $uids[intval($item['student_id'])] = intval($item['student_id']);
        }
        $uids = array_values($uids);

        $serviceUser = new Service_Data_User_Profile();
        $userInfos = $serviceUser->getUserInfoByUids($uids);
        if (empty($userInfos)) {
            return array();
        }

        return $this->formatBase($groupInfo, $userInfos);
    }

    private function formatBase($groupInfo, $lists) {

        $studentPrice = array();
        if (!empty($groupInfo['student_price'])) {
            $studentPrice = json_decode($groupInfo['student_price'], true);
        }

        $result = array(
            array(
                "type"=> "input-text",
                "name"=> "group_id",
                "value" => $groupInfo['id'],
                "hidden" => true,
            ),
            array(
                "type" => "html",
                "html" => "<p style='color:red; margin-bottom:2rem;'>修改单人结算价格, 如不配置, 默认为班级客单价, 配置空或0则为免费</p>"
            )
        );
        foreach ($lists as $item) {
            // 获取实际价格
            $price = intval($groupInfo['price']) / 100;
            if (isset($studentPrice[$item['uid']])) {
                $price = intval($studentPrice[$item['uid']]) / 100;
            }

            $result[] = array(
                "type"=> "input-text",
                "name"=> "su_" . strval($item['uid']),
                "label"=> $item['nickname'],
                "value" => $price,
                "addOn" => array(
                    "type" => "text",
                    "label" => "元"
                ),
            );
            $result[] = array(
                "type" => "divider"
            );
        }
        return $result;
    }
}