<?php

class Service_Data_Roles {

    private $daoRole ;
    private $daoRoleMap ;

    public function __construct() {
        $this->daoRole = new Dao_Roles () ;
        $this->daoRoleMap = new Dao_Rolesmap () ;
    }

    // 根据id获取权限记录
    public function getRolesById ($id) {
        $conds = array(
            'id' => $id,
        );
        $data = $this->daoRole->getRecordByConds($conds, $this->daoRole->arrFieldsMap);
        if (!empty($data)) {
            $data['page_ids'] = explode(",", $data['page_ids']);
        }
        return empty($data) ? array() : $data;
    }

    // 根据name获取权限记录
    public function getRolesByName ($name) {
        $conds = array(
            'name' => $name,
        );
        $data = $this->daoRole->getRecordByConds($conds, $this->daoRole->arrFieldsMap);
        if (!empty($data)) {
            $data['page_ids'] = explode(",", $data['page_ids']);
        }
        return empty($data) ? array() : $data;
    }

    public function getRolesMapById ($rid) {
        $conds = array(
            'role_id' => $rid,
        );

        $data = $this->daoRoleMap->getListByConds($conds, $this->daoRoleMap->arrFieldsMap);
        return empty($data) ? array() : $data;
    }

    public function getRolesMapByUid () {

    }

    public function getListByConds($conds, $filed = array(), $option = null, $append = null) {
        $filed = empty($filed) ? $this->daoRole->arrFieldsMap : $filed;
        $data = $this->daoRole->getListByConds($conds, $filed, $option, $append);
        if (empty($data)) {
            return array();
        }

        foreach ($data as $key => $item) {
            $item['update_time'] = date("Y-m-d H:i:s", $item['update_time']);
            $item['create_time'] = date("Y-m-d H:i:s", $item['create_time']);
            $data[$key] = $item;
        }
        return $data;
    }

    public function getTotalByConds () {
        
    }

    // 根据uid获取页面ids (基础session用, 不要改, 重启一个接口)
    public function getPagesByUid($uid, $type) {
        // 超管权限为空, 默认就是全部, 学生返回空, 就是真的没有
        if ($type == Service_Data_User_Profile::USER_TYPE_SUPER
            || $type == Service_Data_User_Profile::USER_TYPE_STUDENT) {
            return array();
        }

        // 正常从数据库查权限
        $conds = array(
            'uid' => intval($uid),
        );
        $data = $this->daoRoleMap->getListByConds($conds, array("role_id"));
        if (!empty($data)) {
            $rolesIds = array();
            foreach ($data as $key => $item) {
                $rolesIds[intval($item['role_id'])] = intval($item['role_id']);
            }
            $rolesIds = array_values($rolesIds);
    
            // 查询roles中的pageid
            $conds = array(
                sprintf("id in (%s)", implode(",", $rolesIds))
            );
            $data2 = $this->daoRole->getListByConds($conds, array("page_ids"));
            if (!empty($data2)) {
                $pageIds = array();
                foreach ($data2 as $key => $item) {
                    $pageIds = array_merge($pageIds, explode(",", $item['page_ids']));
                }
                $pageIds = array_unique($pageIds);
                $pageIds = array_values($pageIds);
                return $pageIds;
            }
        }
        // 获取默认pages列表
        // 获取menu conf
        $menuConf = Zy_Helper_Config::getAppConfig("menu");

        $pageIds = array();
        if ($type == Service_Data_User_Profile::USER_TYPE_TEACHER) {
            $pageIds = $menuConf['defualt_teacher'];
        } else if ($type == Service_Data_User_Profile::USER_TYPE_ADMIN) {
            $pageIds = $menuConf['defualt_amdins'];
        }
        return $pageIds;
    }

    // 创建权限
    public function createRoles ($profile) {
        return $this->daoRole->insertRecords($profile);
    }

    // 更新权限
    public function updateRoles ($id, $name, $descs, $pageIds, $insetUids, $delUids){
        $this->daoRole->startTransaction();
        $conds = array(
            'id' => $id,
        );
        $profile = array(
            "name" => $name,
            "descs" => $descs,
            "page_ids" => implode(",", $pageIds),
            "update_time" => time(),
        );
        $ret = $this->daoRole->updateByConds($conds, $profile);
        if ($ret == false) {
            $this->daoRole->rollback();
            return false;
        }

        if (!empty($insetUids)) {
            foreach ($insetUids as $uid) {
                $profile = array(
                    "role_id" => $id,
                    "uid" => $uid,
                    "create_time" => time(),
                    "update_time" => time(),
                );
                $ret = $this->daoRoleMap->insertRecords($profile);
                if ($ret == false) {
                    $this->daoRole->rollback();
                    return false;
                }
            }
        }

        if (!empty($delUids)) {
            foreach ($delUids as $uid) {
                $conds = array(
                    "role_id" => $id,
                    "uid" => $uid,
                );
                $ret = $this->daoRoleMap->deleteByConds($conds);
                if ($ret == false) {
                    $this->daoRole->rollback();
                    return false;
                }
            }
        }

        $this->daoRole->commit();
        return  true;
    }

    // 删除权限
    public function deleteRoles ($id) {
        $this->daoRole->startTransaction();
        $conds = array(
            'role_id' => $id,
        );
        $ret = $this->daoRoleMap->deleteByConds($conds);
        if ($ret == false) {
            $this->daoRole->rollback();
            return false;
        }
        $conds = array(
            "id" => $id,
        );
        $ret = $this->daoRole->deleteByConds($conds);
        if ($ret == false) {
            $this->daoRole->rollback();
            return false;
        }
        $this->daoRole->commit();
        return true;
    }
}