
<?php
class GetData {

    public function execute($params) {
        $dao = new Dao_Schedule();
        $daoColumn = new Dao_Column();

        $lists = $dao->getListByConds(array(), $dao->arrFieldsMap);

        foreach ($lists as $item) {

            $conds = array(
                'id' => $item['column_id'],
            );

            $column = $daoColumn->getRecordByConds($conds, $daoColumn->arrFieldsMap);

            if ($column['teacher_id'] != $item['teacher_id']) {
                echo $item['id'] . ", " . $item['teacher_id'] . ", " . $column['teacher_id'] . "\r\n";
                // $conds = array(
                //     'id' => $item['id']
                // );
                // $ret = $dao->updateByConds($conds, array('teacher_id' => $column['teacher_id']));
                // echo  $ret . "\r\n";
            }
        }

        echo "done\r\n";
    }

}
