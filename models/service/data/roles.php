<?php

class Service_Data_Roles {

    private $daoRole ;
    private $daoRoleMap ;

    const ROLE_MODE_SCHEDULE_UPDATE = "4001";
    const ROLE_MODE_SCHEDULE_DELETE = "4002";
    const ROLE_MODE_STUDENT_RECHARGE = "4003";
    const ROLE_MODE_STUDENT_REFUND = "4004";
    const ROLE_MODE_TEACHER_SALARY = "4005";
    const ROLE_MODE_TEACHER_LOCKDEL = "4006";

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
    public function getPageIdsByUid($uid, $type) {
        // 超管权限为空, 默认就是全部, 学生返回空, 就是真的没有
        $pageIds = $modeIds = array();
        if (in_array($type, array(Service_Data_Profile::USER_TYPE_STUDENT, Service_Data_Profile::USER_TYPE_SUPER))) {
            return array($pageIds, $modeIds);
        }

        // 正常从数据库查权限
        $conds = array(
            'uid' => intval($uid),
        );
        $data = $this->daoRoleMap->getListByConds($conds, array("role_id"));
        if (empty($data)) {
            return array($pageIds, $modeIds);  // 不配置谁都没权限
        }

        $rolesIds = Zy_Helper_Utils::arrayInt($data, 'role_id');

        // 查询roles中的pageid
        $conds = array(
            sprintf("id in (%s)", implode(",", $rolesIds))
        );
        $data2 = $this->daoRole->getListByConds($conds, array("page_ids", "mode_ids"));
        if (empty($data2)) {
            return array($pageIds, $modeIds);
        }

        foreach ($data2 as $item) {
            $pageIds = array_merge($pageIds, explode(",", $item['page_ids']));
            $modeIds = array_merge($modeIds, explode(",", $item['mode_ids']));
        }
        $pageIds = array_unique($pageIds);
        $pageIds = array_values($pageIds);

        $modeIds = array_unique($modeIds);
        $modeIds = array_values($modeIds);
        return array($pageIds, $modeIds);
    }

    // 创建权限
    public function createRoles ($profile) {
        return $this->daoRole->insertRecords($profile);
    }

    // 更新权限
    public function updateRoles ($id, $name, $descs, $pageIds, $modeIds, $insetUids, $delUids){
        $this->daoRole->startTransaction();
        $conds = array(
            'id' => $id,
        );
        $profile = array(
            "name" => $name,
            "descs" => $descs,
            "page_ids" => implode(",", $pageIds),
            "mode_ids" => implode(",", $modeIds),
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