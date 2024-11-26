<?php
class UpdateStudentState{

    public function execute($params) {
        $daoUser = new Dao_User();
        $list = $daoUser->getListByConds(array("type=12"), array("uid", "nickname", "create_time",  "state"));
        if (empty($list)) {
            return ;
        }
        $now = time();

        $daoCurriculum = new Dao_Curriculum();
        // 找到每个学生最后一次更新结算订单状态
        foreach ($list as $item) {
            $uid = $item["uid"];
            $nickname = $item["nickname"];
            
            // 已经下线学员不做处理
            if ($item['state'] == Service_Data_Profile::STUDENT_DISABLE) {  
                continue;
            }
            // 获取最后一次排课结算的时间
            $conds = array(
                "student_uid" => $uid, 
                "state" => Service_Data_Schedule::SCHEDULE_DONE,
            );
            $append = array(
                "order by id desc"
            );
            $record = $daoCurriculum->getRecordByConds($conds, array("update_time"), null, $append);
            // 只要12个月没有结算就是完结,  无论是否绑定课程
            if (empty($record))  {
                if ($item['create_time'] < $now - 12*30*86400) {
                    echo sprintf("%d, %s, 无结算记录并且完结 " . date("Y-m-d H:i:s", $item["create_time"]) . "\n", $uid, $nickname);
                    $daoUser->updateByConds(array("uid" => $uid), array("state" => Service_Data_Profile::STUDENT_OVER));
                } else if ($item["create_time"] < $now - 3*30*86400){
                    echo sprintf("%d, %s, 无结算记录并且休眠 " . date("Y-m-d H:i:s", $item["create_time"]) . "\n", $uid, $nickname);
                    $daoUser->updateByConds(array("uid" => $uid), array("state" => Service_Data_Profile::STUDENT_DORMANCY));
                }
                continue;
            } 
            if ($record["update_time"] < $now - 12*30*86400) {
                echo sprintf("%d, %s, 完结" . date("Y-m-d H:i:s", $record["update_time"]) . "\n", $uid, $nickname);
                $daoUser->updateByConds(array("uid" => $uid), array("state" => Service_Data_Profile::STUDENT_OVER));
                continue;
            } 
            // 休眠
            if ($record["update_time"] < $now - 3*30*86400){
                echo sprintf("%d, %s, 休眠" . date("Y-m-d H:i:s", $record["update_time"]) . "\n", $uid, $nickname);
                $daoUser->updateByConds(array("uid" => $uid), array("state" => Service_Data_Profile::STUDENT_DORMANCY));
                continue;
            }
            // 如果状态不是1, 并且近期有结算, 恢复正常
            if ($item['state'] != Service_Data_Profile::STUDENT_ABLE) {
                echo sprintf("%d, %s, 恢复正常" . date("Y-m-d H:i:s", $record["update_time"]) . "\n", $uid, $nickname);
                $daoUser->updateByConds(array("uid" => $uid), array("state" => Service_Data_Profile::STUDENT_ABLE));
                continue;                
            }
        }
        echo date("Y-m-d", $now) . " 完成\n";
    }
}