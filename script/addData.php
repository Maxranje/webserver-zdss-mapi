<?php
class AddData {

    public function execute($params) {

        $this->$params();
    }

    public function changeBalance() {
        $daoUser = new Dao_User();
        $daoCapital = new Dao_Capital();
        $list = $daoCapital->getListByConds(array(), $daoCapital->arrFieldsMap);

        $users = array();
        foreach ($list as $item) {
            if (!isset($users[$item['uid']])) {
                $users[$item['uid']] = 0;
            }
            if ($item['type'] == Service_Data_Profile::RECHARGE) {
                $users[$item['uid']] += $item['capital'];
            } else if ($item['type'] == Service_Data_Profile::REFUND){
                $users[$item['uid']] -= $item['capital'];
            }
        }

        foreach ($users as $u => $v) {
            $uinfo = $daoUser->getListByConds(array('uid'=>$u), $daoUser->arrFieldsMap);
            if (!empty($uinfo)) {
                $ext = empty($uinfo['ext']) ? array() : json_decode($uinfo['ext'], true);
                if (empty($ext['total_balance'])) {
                    $ext['total_balance'] = 0;
                }
                $ext['total_balance'] += $v;
                var_dump($ext);
                $ret = $daoUser->updateByConds(array('uid' => $u), array('ext' => json_encode($ext)));
                var_dump($ret);
                if ($ret == false) {
                    var_dump($u, $v);
                }
            }
        }
        var_dump("done");
    }

    public function addStudent () {
        $daoUser = new Dao_User();
        $defaultProfile = [
            "type"  => Service_Data_Profile::USER_TYPE_STUDENT , 
            "name"  => "zhang.ss", 
            "nickname" => "张思思",
            "phone"  => "1001011", 
            "avatar" => "",
            "school"  => "河北一中", 
            "graduate"  => "三年二班" ,
            "sex"  => "F", 
            "student_capital" => 0,
            "teacher_capital" => 0,
            "create_time"  => time() , 
            "update_time"  => time() , 
        ];
        for ($i = 0; $i < 500; $i++) {
            $profile = $defaultProfile;
            $profile['name'] .= $i;
            $profile['nickname'] .= $i;
            $daoUser->insertRecords($profile);
        }
        echo "done";
    }

    public function addTeacher () {
        $daoUser = new Dao_User();
        $defaultProfile = [
            "type"  => Service_Data_Profile::USER_TYPE_TEACHER, 
            "name"  => "deng.ls", 
            "nickname" => "邓老师",
            "phone"  => "1001011", 
            "avatar" => "",
            "school"  => "", 
            "graduate"  => "" ,
            "sex"  => "M", 
            "student_capital" => 0,
            "teacher_capital" => 0,
            "create_time"  => time() , 
            "update_time"  => time() , 
        ];
        for ($i = 0; $i < 10; $i++) {
            $profile = $defaultProfile;
            $profile['name'] .= $i;
            $profile['nickname'] .= $i;
            $daoUser->insertRecords($profile);
        }
        echo "done";
    }

    public function addAdmin () {
        $daoUser = new Dao_User();
        $defaultProfile = [
            "type"  => Service_Data_Profile::USER_TYPE_ADMIN, 
            "name"  => "pk_00", 
            "nickname" => "pk_00",
            "phone"  => "1001011", 
            "avatar" => "",
            "school"  => "", 
            "graduate"  => "" ,
            "sex"  => "M", 
            "student_capital" => 0,
            "teacher_capital" => 0,
            "create_time"  => time() , 
            "update_time"  => time() , 
        ];
        for ($i = 0; $i < 5; $i++) {
            $profile = $defaultProfile;
            $profile['name'] .= $i;
            $profile['nickname'] .= $i;
            $daoUser->insertRecords($profile);
        }
        echo "done";
    }

    public function addSubject () {
        $daoUser = new Dao_Subject();
        $defaultProfile = [
            [
                "category1"  => "数学", 
                "category2"  => "数学", 
                "name"  => "高等数学", 
                "descs"  =>  "", 
                "create_time" => time(),
                "update_time" => time(),
            ],
            [
                "category1"  => "数学", 
                "category2"  => "数学", 
                "name"  => "线性代数", 
                "descs"  =>  "", 
                "create_time" => time(),
                "update_time" => time(),
            ],
            [
                "category1"  => "语文", 
                "category2"  => "语文", 
                "name"  => "中国古代语文", 
                "descs"  =>  "", 
                "create_time" => time(),
                "update_time" => time(),
            ],
            [
                "category1"  => "语文", 
                "category2"  => "语文", 
                "name"  => "现代文学", 
                "descs"  =>  "", 
                "create_time" => time(),
                "update_time" => time(),
            ],
        ];
        foreach($defaultProfile as $profile) {
            $daoUser->insertRecords($profile);
        }
        echo "done";
    }

}