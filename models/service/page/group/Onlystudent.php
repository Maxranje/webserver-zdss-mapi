<?php

class Service_Page_Group_Onlystudent extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $groupId = empty($this->request['group_id']) ? 0 : intval($this->request['group_id']);

        if ($groupId <= 0) {
            return array();
        }

        $serviceGroupMap = new Service_Data_User_Group();
        $studentUids = $serviceGroupMap->getListByConds(array('group_id' => $groupId), array("student_id"));
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

        return $this->formatBase($userInfos);
    }

    private function formatBase($lists) {
        $options = array();
        foreach ($lists as $item) {
            $optionsItem = [
                'label' => $item['nickname'],
                'value' => $item['uid'],
            ];
            $options[] = $optionsItem;
        }
        return $options;
    }
}