
<?php
class CheckData {

    public function execute($params) {

        $this->$params();

        echo "done\r\n";
    }

    public function column () {
                
        $daoUser = new Dao_User();
        $daoColumn = new Dao_Column();
        $daoSubject = new Dao_Subject();

        $lists = $daoColumn->getListByConds(array(), array("teacher_uid", "subject_id"));

        foreach ($lists as $item) {

            $conds = array(
                'uid' => $item['teacher_uid'],
            );

            $user = $daoUser->getRecordByConds($conds, $daoUser->arrFieldsMap);
            if (empty($user)) {
                var_dump($item['teacher_uid']);
            }

            $conds = array(
                'id' => $item['subject_id'],
            );

            $subject = $daoSubject->getRecordByConds($conds, $daoSubject->arrFieldsMap);
            if (empty($subject)) {
                var_dump($item['subject_id']);
            }
        }
    }

    public function order () {
        
        $daoOrder = new Dao_Order();
        $daoUser = new Dao_User();
        $daoColumn = new Dao_Column();
        $daoSubject = new Dao_Subject();
        $daoB = new Dao_Birthplace();
        $daoC = new Dao_Clasze();

        $lists = $daoOrder->getListByConds(array(), $daoOrder->arrFieldsMap);

        foreach ($lists as $item) {

            // $conds = array(
            //     'uid' => $item['teacher_uid'],
            // );

            // $user = $daoUser->getRecordByConds($conds, $daoUser->arrFieldsMap);
            // if (empty($user)) {
            //     var_dump($item['teacher_uid']);
            // }

            $conds = array(
                'uid' => $item['student_uid'],
            );

            $user = $daoUser->getRecordByConds($conds, $daoUser->arrFieldsMap);
            if (empty($user)) {
                var_dump("student " . $item['student_uid']);
            }

            $conds = array(
                'id' => $item['bpid'],
            );

            $user = $daoB->getRecordByConds($conds, $daoB->arrFieldsMap);
            if (empty($user)) {
                var_dump("bid " . $item['bpid']);
            }


            $conds = array(
                'id' => $item['cid'],
            );

            $user = $daoC->getRecordByConds($conds, $daoC->arrFieldsMap);
            if (empty($user)) {
                var_dump("cid " . $item['cid']);
            }


            $conds = array(
                'uid' => $item['student_uid'],
            );

            $user = $daoUser->getRecordByConds($conds, $daoUser->arrFieldsMap);
            if (empty($user)) {
                var_dump("student " . $item['student_uid']);
            }

            $conds = array(
                'id' => $item['subject_id'],
            );

            $subject = $daoSubject->getRecordByConds($conds, $daoSubject->arrFieldsMap);
            if (empty($subject)) {
                var_dump($item['subject_id']);
            }
        }
    }

}
