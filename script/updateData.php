<?php
class UpdateData {

    public function execute($params) {
        $daoUser = new Dao_User();

        $lists = $daoUser->getListByConds(array(), $daoUser->arrFieldsMap);

        foreach ($lists as $item) {
            $nickName = $item['nickname'];
            $name = $item['name'];

            $uid = $item['uid'];
            $item['name'] = $nickName;
            $item['nickname'] = $name;
            unset($item['id']);

            $conds = array('uid' => $uid);
            $daoUser->updateByConds($conds, $item);
        }
    }

}