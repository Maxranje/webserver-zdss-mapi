<?php

class Service_Page_Roles_Userlists extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkSuper()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $rid        = empty($this->request['rid']) ? 0 : intval($this->request['rid']);
        $nickname   = empty($this->request['nickname']) ? "" : strval($this->request['nickname']);
        if ($rid <= 0) {
            throw new Zy_Core_Exception(405, "需要选定一个角色");
        }

        // 查询符合条件的人
        $conds = array(
            sprintf("type in (%s)", implode(",", [
                Service_Data_User_Profile::USER_TYPE_TEACHER,
                Service_Data_User_Profile::USER_TYPE_ADMIN
            ]))
        );

        if (!empty($nickname)) {
            $conds[] = "nickname like '%".$nickname."%'";
        }

        $serviceData = new Service_Data_User_Profile();

        $lists = $serviceData->getListByConds($conds);
        
        return $this->formatSelect ($rid, $lists);
    }

    private function formatSelect ($rid, $lists) {
        $options = array(
            array(
                "label" =>  "管理员",
                "children" =>  []
            ),
            array(
                "label" =>  "教师",
                "children" =>  []
            ),
        );
        foreach ($lists as $item) {
            $k = $item['type'] == Service_Data_User_Profile::USER_TYPE_ADMIN ? 0 : 1;
            $options[$k]['children'][] = array(
                'label' => $item['nickname'],
                'value' => $item['uid'],
            );
        }
        $options = array_values($options);
        $values = array();

        // 查询已选中人
        $serviceRoles = new Service_Data_Roles();
        $miList = $serviceRoles->getRolesMapById($rid);
        if (!empty($miList)) {
            foreach ($miList as $t) {
                $values[]= $t['uid'];
            }
        }
        $values = implode(",", $values);
        return array('options' => $options, 'value' => $values);
    }
}