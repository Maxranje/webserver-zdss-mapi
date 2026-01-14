<?php

/**
 * SQL构造器公共类
 *
 */

class Zy_Database_Dbsqlmaker
{
    const LIST_COM = 0;
    const LIST_AND = 1;
    const LIST_SET = 2;

    private static $sql;
    private static $db;

    /**
    * @brief 获取sql
    *
    * @return
    */
    public function getSQL()
    {
        return self::$sql;
    }

    /**
    * @brief 获取select语句
    *
    * @param $tables 表名
    * @param $fields 字段名
    * @param $conds 条件
    * @param $options 选项
    * @param $appends 结尾操作
    *
    * @return
    */
    public static function getSelect($_db, $tables, $fields, $conds = NULL, $options = NULL, $appends = NULL, $is_prepared = false, &$bindParam = false)
    {
        $sql = 'SELECT ';
        self::$db = $_db;

        // 1. options
        if(!empty($options))
        {
            $options = self::__makeList($options, self::LIST_COM, ' ');
            if(!strlen($options))
            {
                self::$sql = NULL;
                return NULL;
            }
            $sql .= "$options ";
        }

        // 2. fields
        $fields = self::__makeList($fields, self::LIST_COM);
        if(!strlen($fields))
        {
            self::$sql = NULL;
            return NULL;
        }
        $sql .= "$fields FROM ";

        // 3. from
        $tables = self::__makeList($tables, self::LIST_COM);
        if(!strlen($tables))
        {
            self::$sql = NULL;
            return NULL;
        }
        $sql .= $tables;

        // 4. conditions
        if(!empty($conds))
        {
            if ($is_prepared) {
                list($conds, $bindParam) = self::__makeListPrepared($conds, self::LIST_AND);
            } else {
                $conds = self::__makeList($conds, self::LIST_AND);
            }

            if(!strlen($conds)) {
                self::$sql = NULL;
                return NULL;
            }
            $sql .= " WHERE $conds";            

        }

        // 5. other append
        if(!empty($appends))
        {
            $appends = self::__makeList($appends, self::LIST_COM, ' ');
            if(!strlen($appends))
            {
                self::$sql = NULL;
                return NULL;
            }
            $sql .= " $appends";
        }
        
        if (ENV == "development") {
            Zy_Helper_Log::addnotice("sql: [" . $sql . "] bind: [". json_encode($bindParam) ."]" );
        }
        self::$sql = $sql;
        return $sql;
    }

    /**
    * @brief 获取update语句
    *
    * @param $table 表名
    * @param $row 字段
    * @param $conds 条件
    * @param $options 选项
    * @param $appends 结尾操作
    *
    * @return
    */
    public static function getUpdate($_db, $table, $row, $conds = NULL, $options = NULL, $appends = NULL, $is_prepared = false, &$bindParam = false)
    {
        self::$db = $_db;
        if(empty($row))
        {
            return NULL;
        }
        return self::__makeUpdateOrDelete($table, $row, $conds, $options, $appends, $is_prepared, $bindParam);
    }

    /**
    * @brief 获取delete语句
    *
    * @param $table
    * @param $conds
    * @param $options
    * @param $appends
    *
    * @return
    */
    public static function getDelete($_db, $table, $conds = NULL, $options = NULL, $appends = NULL, $is_prepared = false , &$bindParam = false)
    {
        self::$db = $_db;
        return self::__makeUpdateOrDelete($table, NULL, $conds, $options, $appends, $is_prepared, $bindParam);
    }

    private static function __makeUpdateOrDelete($table, $row, $conds, $options, $appends, $is_prepared, &$bindParam)
    {
        // 1. options
        $bindRows = $bindConds = array();
        if(!empty($appends))
        {
            if(is_array($options))
            {
                $options = implode(' ', $options);
            }
            $sql = $options;
        }

        // 2. fields
        // delete
        if(empty($row))
        {
            $sql = "DELETE $options FROM $table ";
        }
        // update
        else
        {
            $sql = "UPDATE $options $table SET ";
            if ($is_prepared) {
                list($row, $bindRows) = self::__makeListPrepared($row, Zy_Database_Dbsqlmaker::LIST_SET);
            } else {
                $row = self::__makeList($row, Zy_Database_Dbsqlmaker::LIST_SET);
            }
            if(!strlen($row))
            {
                self::$sql = NULL;
                return NULL;
            }
            $sql .= "$row ";
        }

        // 3. conditions
        if(!empty($conds))
        {
            if ($is_prepared) {
                list($conds, $bindConds) = self::__makeListPrepared($conds, Zy_Database_Dbsqlmaker::LIST_AND);
            } else {
                $conds = self::__makeList($conds, Zy_Database_Dbsqlmaker::LIST_AND);
            }
            if(!strlen($conds))
            {
                self::$sql = NULL;
                return NULL;
            }
            $sql .= "WHERE $conds ";
        }

        // 4. other append
        if(!empty($appends))
        {
            $appends = self::__makeList($appends, Zy_Database_Dbsqlmaker::LIST_COM, ' ');
            if(!strlen($appends))
            {
                self::$sql = NULL;
                return NULL;
            }
            $sql .= $appends;
        }

        $bindParam = array_merge($bindRows, $bindConds);

        self::$sql = $sql;
        return $sql;
    }

    /**
    * @brief 获取insert语句
    *
    * @param $table 表名
    * @param $row 字段
    * @param $options 选项
    * @param $onDup 键冲突时的字段值列表
    *
    * @return
    */
    public static function getInsert($_db, $table, $row, $options = NULL, $onDup = NULL, $is_prepared =false, &$bindParam = false)
    {

        $sql = 'INSERT ';

        self::$db = $_db;
        // 1. options
        if(!empty($options))
        {
            if(is_array($options))
            {
                $options = implode(' ', $options);
            }
            $sql .= "$options ";
        }

        // 2. table
        $sql .= "$table SET ";

        // 3. clumns and values
        if ($is_prepared) {
            list($row, $bindParam) = self::__makeListPrepared($row, self::LIST_SET);
        } else {        
            $row = self::__makeList($row, self::LIST_SET);
        }
        if(!strlen($row))
        {
            self::$sql = NULL;
            return NULL;
        }
        $sql .= $row;

        if(!empty($onDup))
        {
            $sql .= ' ON DUPLICATE KEY UPDATE ';
            $onDup = self::__makeList($onDup, Zy_Database_Dbsqlmaker::LIST_SET);
            if(!strlen($onDup))
            {
                self::$sql = NULL;
                return NULL;
            }
            $sql .= $onDup;
        }
        self::$sql = $sql;
        return $sql;
    }

    private static function __makeList($arrList, $type = Zy_Database_Dbsqlmaker::LIST_SET, $cut = ', ')
    {
        if(is_string($arrList))
        {
            return $arrList;
        }

        $sql = '';

        // for set in insert and update
        if($type == Zy_Database_Dbsqlmaker::LIST_SET)
        {
            foreach($arrList as $name => $value)
            {
                if(is_int($name))
                {
                    $sql .= "$value, ";
                }
                else
                {
                    if(!is_int($value))
                    {
                        if($value === NULL)
                        {
                            $value = 'NULL';
                        }
                        else
                        {
                            $value = '\''.self::$db->escape_str($value).'\'';
                        }
                    }
                    $sql .= "$name=$value, ";
                }
            }
            $sql = substr($sql, 0, strlen($sql) - 2);
        }
        // for where conds
        else if($type == Zy_Database_Dbsqlmaker::LIST_AND)
        {
            foreach($arrList as $name => $value)
            {
                if(is_int($name))
                {
                    $sql .= "($value) AND ";
                }
                else
                {
                    if(!is_int($value))
                    {
                        if($value === NULL)
                        {
                            $value = 'NULL';
                        }
                        else
                        {
                            $value = '\''.self::$db->escape_str($value).'\'';
                        }
                    }
                    $sql .= "($name $value) AND ";
                }
            }
            $sql = substr($sql, 0, strlen($sql) - 5);
        }
        else
        {
            $sql = implode($cut, $arrList);
            if (!is_array($arrList)) {
                print_r(debug_backtrace());
            }
        }

        return $sql;
    }

    /**
     * 创建预处理条件的列表
     */
    private static function __makeListPrepared($arrList, $type = self::LIST_SET)
    {
        if(is_string($arrList)) 
        {
            return array($arrList, array());
        }

        $sql = '';
        $binds = array();

        if($type == self::LIST_SET) 
        {
            foreach($arrList as $name => $value) 
            {
                if(is_int($name)) 
                {
                    $sql .= "$value, ";
                } else 
                {
                    $sql .= "$name=?, ";
                    $binds[] = $value;
                }
            }
            $sql = substr($sql, 0, strlen($sql) - 2);
        } 
        else if($type == self::LIST_AND) 
        {
            foreach($arrList as $name => $value) 
            {
                if(is_int($name)) 
                {
                    // 处理操作符
                    if (strpos($value, ' ') !== false) 
                    {
                        list($field, $operator, $value) = explode(' ', $value, 3);
                        $operator = strtoupper(trim($operator));
                        
                        // 处理不同的操作符
                        if ($operator === 'LIKE') 
                        {
                            $sql .= "($field LIKE ?) AND ";
                            $value = trim(trim($value, "'"), "'");
                            $binds[] = $value;
                        } 
                        else if ($operator === 'IN') 
                        {
                            if (is_string($value)) 
                            {
                                $value = explode(",",trim(trim($value, "("), ")"));
                                if (count(array_filter($value, 'is_numeric')) === count($value))
                                {
                                    $value = Zy_Helper_Utils::arrayInt($value);
                                }
                            }
                            if (is_array($value)) 
                            {
                                $placeholders = implode(',', array_fill(0, count($value), '?'));
                                $sql .= "($field IN ($placeholders)) AND ";
                                $binds = array_merge($binds, $value);
                            } 
                            else 
                            {
                                $sql .= "($field IN (?)) AND ";
                                $binds[] = $value;
                            }
                        } 
                        elseif ($operator === 'BETWEEN') 
                        {
                            if (is_array($value) && count($value) === 2) 
                            {
                                $sql .= "($field BETWEEN ? AND ?) AND ";
                                $binds = array_merge($binds, $value);
                            } 
                            else 
                            {
                                // 无效的BETWEEN值，跳过
                                continue;
                            }
                        } 
                        elseif ($operator === 'IS') 
                        {
                            if ($value === null) 
                            {
                                $sql .= "($field IS NULL) AND ";
                            } 
                            else 
                            {
                                $sql .= "($field IS ?) AND ";
                                $binds[] = $value;
                            }
                        } 
                        else 
                        {
                            // 处理其他操作符 (=, !=, >, <, >=, <=)
                            $sql .= "($field $operator ?) AND ";
                            $binds[] = $value;
                        }
                    } 
                    else 
                    {
                        $sql .= "($value) AND ";
                    }
                } 
                else 
                {
                    $sql .= "($name = ?) AND ";
                    $binds[] = $value;
                }
            }
            $sql = substr($sql, 0, strlen($sql) - 5);
        } 
        else 
        {
            $sql = implode(', ', $arrList);
        }
        return array($sql, $binds);
    }    
}
