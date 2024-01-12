<?php

class Service_Page_Teacher_Lock_Lists extends Zy_Core_Service{

    public function execute () {
        if (!$this->checkAdmin()) {
            throw new Zy_Core_Exception(405, "无权限查看");
        }


        $nickname   = empty($this->request['nickname']) ? "" : strval($this->request['nickname']);
        $dataRange  = empty($this->request['date_rangee']) ? array() : explode(",", $this->request['date_rangee']);
        $pn         = empty($this->request['page']) ? 1 : intval($this->request['page']);
        $rn         = empty($this->request['perPage']) ? 20 : intval($this->request['perPage']);
        $pn         = ($pn-1) * $rn;

        $conds = array();
        if (!empty($nickname)) {
            $serviceUser = new Service_Data_Profile();
            $userInfos = $serviceUser->getListByConds(array("nickname like '%$nickname%'"));
            if (empty($userInfos)) {
                return array();
            }   
            $uids = Zy_Helper_Utils::arrayInt($userInfos, "uid");
            $conds[] = sprintf("uid in (%s)", implode(",", $uids));
        }

        if (!empty($dataRange)) {
            $conds[] = "start_time >= ". $dataRange[0];
            $conds[] = "end_time <= ". ($dataRange[1] + 1);
        }

        $arrAppends[] = 'order by id desc';
        $arrAppends[] = "limit {$pn} , {$rn}";

        $serviceData = new Service_Data_Lock();
        $lists = $serviceData->getListByConds($conds, array(), NULL, $arrAppends);
        if (empty($lists)) {
            return array();
        }

        $total = $serviceData->getTotalByConds($conds);

        return array(
            'lists' => $this->format($lists),
            'total' => $total,
        );
    }

    public function format ($lists) {
        if (empty($lists)) {
            return array();
        }

        $isLD = $this->isModeAble(Service_Data_Roles::ROLE_MODE_TEACHER_LOCKDEL);

        $teacherUids = Zy_Helper_Utils::arrayInt($lists, "uid");
        $operators = Zy_Helper_Utils::arrayInt($lists, "operator");

        $uids = array_unique(array_merge($teacherUids, $operators));

        $serviceUser = new Service_Data_Profile();
        $userInfos = $serviceUser->getListByConds(array(sprintf('uid in (%s)', implode(',', $uids))));
        $userInfos = array_column($userInfos, null, 'uid');

        $result = array();
        foreach ($lists as $item) {
            if (empty($userInfos[$item['uid']]['nickname'])) {
                continue;
            }
            if (empty($userInfos[$item['operator']]['nickname'])) {
                continue;
            }
            $tmp = array();
            $tmp["is_ld"] = $isLD ? 1 : 0;
            $tmp['lock_time'] = sprintf("%s %s - %s",date('Y年m月d日', $item['start_time']), date('H:i', $item['start_time']), date('H:i', $item['end_time'])); 
            $tmp['nickname'] = $userInfos[$item['uid']]['nickname'];
            $tmp['id'] = $item['id'];
            $tmp['uid'] = $item['uid'];
            $tmp['operator'] = $userInfos[$item['operator']]['nickname'];
            $tmp['create_time'] = date('Y年m月d日', $item['create_time']);
            $tmp['update_time'] = date('Y年m月d日', $item['update_time']);

            $result[] = $tmp;
        }

        return $result;
    }
}