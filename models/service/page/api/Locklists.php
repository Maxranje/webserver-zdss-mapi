<?php

class Service_Page_Api_Locklists extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }

        $output = array(
            'lists' => array(),
            'total' => 0,
        );

        $pn = empty($this->request['page']) ? 1 : intval($this->request['page']);
        $rn = empty($this->request['perPage']) ? 20 : intval($this->request['perPage']);
        $nickname = empty($this->request['nickname']) ? "" : strval($this->request['nickname']);
        $dataRange  = empty($this->request['daterangee']) ? array() : explode(",", $this->request['daterangee']);

        $pn = ($pn-1) * $rn;

        $uids = array();
        if (!empty($nickname)) {
            $conds = array(
                "nickname like '%".$nickname."%'"
            );
            $serviceUser = new Service_Data_User_Profile();
            $userInfos = $serviceUser->getListByConds($conds);
            if (empty($userInfos)) {
                return $output;
            }

            $uids = array();
            foreach ($userInfos as $item) {
                $uids[intval($item['uid'])] = intval($item['uid']);
            }
            $uids = array_values($uids);
        }
        
        $serviceData = new Service_Data_Lock();

        $conds = array();
        if (!empty($dataRange)) {
            $conds[] = "start_time >= ". $dataRange[0];
            $conds[] = "end_time <= ". ($dataRange[1] + 1);
        }
        if (!empty($uids)) {
            $conds[] = sprintf("uid in (%s)", implode(",", $uids));
        }
        $arrAppends[] = 'order by create_time desc';
        $arrAppends[] = "limit {$pn} , {$rn}";

        $lists = $serviceData->getListByConds($conds, array(), NULL, $arrAppends);
        $total = $serviceData->getTotalByConds($conds);

        $lists = $this->format($lists);

        return array(
            'lists' => $lists,
            'total' => $total,
        );
    }

    public function format ($lists) {
        if (empty($lists)) {
            return array();
        }

        $uid = array();
        foreach ($lists as $key => $item) {
            $uid[intval($item['uid'])] = intval($item['uid']);
        }
        $uid = array_values($uid);

        $serviceUser = new Service_Data_User_Profile();
        $userInfos = $serviceUser->getListByConds(array('uid in ('.implode(',', $uid).')'));
        $userInfos = array_column($userInfos, null, 'uid');


        foreach ($lists as $key => $item) {
            if (empty($userInfos[$item['uid']])) {
                unset($lists[$key]);
                continue;
            }
            $item['lock_time'] = date('Y-m-d H:i', $item['start_time']) . "-".date('H:i', $item['end_time']); 
            $item['nickname'] = $userInfos[$item['uid']]['nickname'];
            $lists[$key] = $item;
        }

        return array_values($lists);
    }
}