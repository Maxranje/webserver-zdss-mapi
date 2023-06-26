<?php
class UpdateSchedule {

    public function execute($params) {
        $dao = new Dao_Schedule();
        $daoColumn = new Dao_Column();

        $lists = $dao->getListByConds(array(), $dao->arrFieldsMap);

        foreach ($lists as $item) {

            $conds = array(
                'id' => $item['column_id'],
            );

            $column = $daoColumn->getRecordByConds($conds, $daoColumn->arrFieldsMap);

            if (!empty($column['teacher_id'])) {
                $conds = array(
                    'id' => $item['id']
                );
                $ret = $dao->updateByConds($conds, array('teacher_id' => $column['teacher_id']));
                if (!$ret) {
                    echo $item['id'] . " - failed \n\r";
                } else {
                    echo $item['id'] . " - d \n\r";
                }
            }
        }

        echo "done\r\n";
    }

}