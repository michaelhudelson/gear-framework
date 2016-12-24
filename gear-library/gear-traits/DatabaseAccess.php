<?php
////////////////////////////////////////////////////////////////////////////////
//
// DATABASE ACCESS
//
// TABLE OF CONTENTS
// -----------------
// public static function updateDatabaseStructure()
// public static function databaseConnection($host = DB_HOST, $name = DB_NAME, $user = DB_USER, $password = DB_PASSWORD)
// public static function mysqliConnection($host = DB_HOST, $name = DB_NAME, $user = DB_USER, $password = DB_PASSWORD)
// public static function pdoConnection($host = DB_HOST, $name = DB_NAME, $user = DB_USER, $password = DB_PASSWORD)
// 
// public static function query($query, $values = array(), $parms = array())
// 
// public static function insertTableRow($table, $column_values)  
//   
// public static function updateTableWhere($table, $column_values, $where, $values = array())
// public static function updateTableRow($table, $id, $column_values)
// public static function updateTable($table, $column_values, $parms)
// 
// public static function deleteTableWhere($table, $where, $values = array())
// public static function deleteTableRow($table, $id)
// public static function deleteTable($table, $parms)
// 
// public static function selectTableWhere($table, $where, $values = array(), $parms = array())
// public static function selectTableOrderBy($table, $order_by, $parms = array())
// public static function selectTableRow($table, $id)
// public static function selectTable($table, $parms = array())
// 
// public static function simpleSelectJoin($left_table, $join, $right_table,  $left_on, $right_on, $parms = array())
// public static function selectJoin($table[, $join, $table,  $left_on, $right_on...], $parms = array())
// 
// public static function referenceTableWhere($table, $key_col, $value_col, $where, $values = array(), $parms = array())
// public static function referenceTableGroupBy($table, $key_col, $value_col, $group_by, $parms = array())
// public static function referenceTable($table, $key_col, $value_col, $parms = array())
// 
// protected static function processParms(&$query, &$values, &$parms)
// protected static function processValues(&$values, $parm_values)
// 
////////////////////////////////////////////////////////////////////////////////

namespace Gear;

trait DatabaseAccess
{
    // keep the databases structure up to date with tables.sql
    public static function updateDatabaseStructure()
    {
        // require file with dbDelta
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // get tables.sql
        $tables_file = GF_DATA_PATH . '/tables.sql';
        
        $return = array();
        // check if file exists
        if (file_exists($tables_file)) {
            // check if file is empty
            if (filesize($tables_file) > 0) {
                // open file
                $file = fopen($tables_file, 'r');

                // read all contents from file
                $sql = fread($file, filesize($tables_file));

                // close file
                fclose($file);

                // run sql
                $return = \dbDelta($sql);
            }
        } else {
            self::debug("$tables_file: Does not exist");
        }
        
        return $return;
    }
    
    // database connection
    public static function databaseConnection($host = DB_HOST, $name = DB_NAME, $user = DB_USER, $password = DB_PASSWORD)
    {
        self::$db_host = $host;
        self::$db_name = $name;
        self::$db_user = $user;
        self::$db_password = $password;
    }
    
    // connects to mysqli with global connection information - default is wordpress
    public static function mysqliConnection($host = DB_HOST, $name = DB_NAME, $user = DB_USER, $password = DB_PASSWORD)
    {
        self::databaseConnection($host, $name, $user, $password);
        $connection = new \mysqli(self::$db_host, self::$db_user, self::$db_password, self::$db_name);
        if ($connection->connect_error) {
            die("Connection failed: " . $connection->connect_error);
        }
        return $connection;
    }

    // connects to pdo mysql with global connection information - default is wordpress
    public static function pdoConnection($host = DB_HOST, $name = DB_NAME, $user = DB_USER, $password = DB_PASSWORD)
    {
        self::databaseConnection($host, $name, $user, $password);
        try {
            $connection = new \PDO('mysql:host=' . self::$db_host . ';dbname=' . self::$db_name . ';charset=utf8', self::$db_user, self::$db_password);
            $connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        } catch (\PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
        return $connection;
    }
    
    ////////////////////////////////////////////////////////////////////////////////

    // run provided query using pdo
    public static function query($query, $values = array(), $parms = array())
    {
        // process parms
        self::processParms($query, $values, $parms);
        
        // check if query debug is enabled
        if ($GLOBALS['gf_query_debug']) {
            self::debug($query);
            self::debug($values);
            self::debug($parms);
        }
        
        // connect and run query
        $pdo = self::pdoConnection();

        try {
            $stmt = $pdo->prepare($query);
            $stmt->execute($values);
        } catch (\PDOException $e) {
            $message = array();
            $message[] = $query;
            $message[] = $values;
            $message[] = $e->getMessage();
            self::systemError($message);
        }
        
        // return insert id if INSERT
        if (stripos($query, 'INSERT') === 0) {
            return $pdo->lastInsertId();
        }
        
        // return rows affected by UPDATE
        if (stripos($query, 'UPDATE') === 0) {
            return $stmt->rowCount();
        }
        
        // return rows affected by DELETE
        if (stripos($query, 'DELETE') === 0) {
            return $stmt->rowCount();
        }
        
        // return associative array for everything else
        if ($stmt->rowCount() !== 0) {
            try {
                return $stmt->fetchAll(\PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                self::debug("Caught exception in <b>" . __FILE__ . "</b> on line <b>" . __LINE__ . "</b>: {$e->getMessage()}");
                return array();
            }
        } else {
            return array();
        }
    }
    
    ////////////////////////////////////////////////////////////////////////////////

    // insert values into table
    public static function insertTableRow($table, $column_values)
    {
        // prep query, makers, and values
        $query = "INSERT INTO $table (";
        $markers = '';
        $values = array();

        // build query, markers, and values
        foreach ($column_values as $column => $value) {
            $query .= "$column, ";
            $markers .= '?, ';
            array_push($values, $value);
        }

        // trim trailing ', ' from query and markers
        $markers = rtrim($markers, ', ');
        $query = rtrim($query, ', ');

        // combine query with markers
        $query .= ") VALUES ($markers)";
        
        return self::query($query, $values);
    }
    
    ////////////////////////////////////////////////////////////////////////////////
    
    // update table where
    public static function updateTableWhere($table, $column_values, $where, $values = array())
    {
        return self::updateTable($table, $column_values, array('where' => $where, 'where_values' => $values));
    }

    // update table row
    public static function updateTableRow($table, $id, $column_values)
    {
        return self::updateTable($table, $column_values, $parms = array('where' => 'id = ?', 'where_values' => $id));
    }
    
    // update table with column_value based on parms
    public static function updateTable($table, $column_values, $parms)
    {
        // prep query and values
        $query = "UPDATE $table SET ";
        $values = array();
        
        // build query and values
        foreach ($column_values as $column => $value) {
            $query .= "$column = ?, ";
            array_push($values, $value);
        }

        // trim trailing ', ' from query
        $query = rtrim($query, ', ');
        
        // run query
        return self::query($query, $values, $parms);
    }
    
    ////////////////////////////////////////////////////////////////////////////////

    // delete from table where
    public static function deleteTableWhere($table, $where, $values = array())
    {
        return self::deleteTable($table, array('where' => $where, 'where_values' => $values));
    }

    // delete row from table
    public static function deleteTableRow($table, $id)
    {
        return self::deleteTable($table, array('where' => 'id = ?', 'where_values' => $id));
    }

    // delete from table
    public static function deleteTable($table, $parms)
    {
        return self::query("DELETE FROM $table", array(), $parms);
    }
    
    ////////////////////////////////////////////////////////////////////////////////
    
    // select all rows where
    public static function selectTableWhere($table, $where, $values = array(), $parms = array())
    {
        return self::selectTable($table, $parms + array('where' => $where, 'where_values' => $values));
    }
    
    // select all rows order by
    public static function selectTableOrderBy($table, $ordery_by, $parms = array())
    {
        return self::selectTable($table, $parms + array('order_by' => $ordery_by));
    }

    // select row from table
    public static function selectTableRow($table, $id)
    {
        $rows = self::query("SELECT * FROM $table WHERE id = ?", $id);
        
        // return one row or empty set
        if (empty($rows)) {
            return array();
        } else {
            return $rows[0];
        }
    }

    // select all rows from table that meets condition
    public static function selectTable($table, $parms = array())
    {
        return self::query("SELECT * FROM $table", array(), $parms);
    }
    
    ////////////////////////////////////////////////////////////////////////////////
    
    // simple select for joined table data
    public static function simpleSelectJoin($left_table, $join, $right_table, $left_on, $right_on, $parms = array())
    {
        $values = array();
        $query .= "SELECT ";
        
        // maintain list of columns being returned
        $col_fields = array();
        
        // get all columns for the left table
        $left_cols = self::query("DESCRIBE $left_table");
        foreach ($left_cols as $col) {
            // track all column names used
            array_push($col_fields, $col['Field']);
            $query .= "$left_table.{$col['Field']} AS {$col['Field']}, ";
        }
        
        // get all columns for the right table
        $right_cols = self::query("DESCRIBE $right_table");
        foreach ($right_cols as $col) {
            // check if column name is available, else prepend the table name to it
            if (in_array($col['Field'], $col_fields)) {
                $query .= "$right_table.{$col['Field']} AS `{$right_table}.{$col['Field']}`, ";
            } else {
                $query .= "$right_table.{$col['Field']} AS {$col['Field']}, ";
            }
        }
        
        $query = rtrim($query, ', ') . " FROM $left_table $join JOIN $right_table ON $left_on = $right_on";
        
        return self::query($query, $values, $parms);
    }
    
    // complex select for data in multiple joined tables
    public static function selectJoin()
    {
        // args: $table[, $join, $table,  $left_on, $right_on...], $parms = array()
        // process args
        $args = func_get_args();
        $arg_count = func_num_args();
        
        // get initial table and it's alias
        $table = $alias = $args[0];
        if (stripos($table, ' as ') !== false) {
            $pieces = explode(' as ', strtolower($table));
            $table = $pieces[0];
            $alias = $pieces[1];
        }
        
        // get additional tables and their aliases
        $joins = array();
        for ($i = 1; $i + 4 <= $arg_count; $i = $i + 4) {
            $join = array();
            $join['join'] = strtoupper($args[$i]);
            
            $join['table'] = $join['alias'] = $args[$i + 1];
            
            // get additional tables and their aliases
            if (stripos($join['table'], ' as ')) {
                $pieces = explode(' as ', strtolower($join['table']));
                $join['table'] = $pieces[0];
                $join['alias'] = $pieces[1];
            }
            
            $join['left_on'] = $args[$i + 2];
            $join['right_on'] = $args[$i + 3];
            $parms = $args[$i + 4];
            
            array_push($joins, $join);
        }
        
        // begin processing args recieved
        $query .= "SELECT ";
        $values = array();
        
        // get all columns for the initial table using the alias
        $cols = self::query("DESCRIBE $table");
        foreach ($cols as $col) {
            $query .= "$alias.{$col['Field']} AS `{$alias}.{$col['Field']}`, ";
        }
        
        // get all columns for all additional joined tables using their alias
        foreach ($joins as $join) {
            $cols = self::query("DESCRIBE {$join['table']}");
            foreach ($cols as $col) {
                $query .= "{$join['alias']}.{$col['Field']} AS `{$join['alias']}.{$col['Field']}`, ";
            }
        }
        
        // trim query and add the initial table and it's alias
        $query = rtrim($query, ', ') . " FROM $table AS $alias";
        
        // join all additional tables
        foreach ($joins as $join) {
            $query .= " {$join['join']} JOIN {$join['table']} AS {$join['alias']} ON {$join['left_on']} = {$join['right_on']}";
        }
        
        return self::query($query, $values, $parms);
    }
    
    ////////////////////////////////////////////////////////////////////////////////
    
    // build an array where array[$key_col] == $value_col
    public static function referenceTableWhere($table, $key_col, $value_col, $where, $values = array(), $parms = array())
    {
        return self::referenceTable($table, $key_col, $value_col, $parms + array('where' => $where, 'where_values' => $values));
    }
    
    public static function referenceTableGroupBy($table, $key_col, $value_col, $group_by, $parms = array())
    {
        return self::referenceTable($table, $key_col, $value_col, $parms + array('group_by' => $group_by));
    }
    
    // build reference array indexed by column
    public static function referenceTable($table, $key_col, $value_col, $parms = array())
    {
        $rows = self::query("SELECT `$key_col`, `$value_col` FROM $table", array(), $parms);
        
        $return = array();
        foreach ($rows as $row) {
            $return[$row[$key_col]] = $row[$value_col];
        }
        
        return $return;
    }
    
    ////////////////////////////////////////////////////////////////////////////////
    
    // append parms to query
    protected static function processParms(&$query, &$values, &$parms)
    {
        // make sure $values is an array
        $values = (array)$values;
        
        // replace column select
        if (isset($parms['select'])) {
            if (stripos($query, 'SELECT') === 0) {
                $start_after_select = 7;
                $length_to_from = stripos($query, ' FROM ') - $start_after_select;
                $query = substr_replace($query, $parms['select'], $start_after_select, $length_to_from);
            }
            self::processValues($values, $parms['select_values']);
        }
        
        // add additional columns to select
        if (isset($parms['select_additional'])) {
            if (stripos($query, 'SELECT') === 0) {
                $start_before_from = stripos($query, ' FROM ');
                $query = substr_replace($query, ', '.$parms['select_additional'], $start_before_from, 0);
            }
            self::processValues($values, $parms['select_additional_values']);
        }
        
        // add where clause if set
        if (isset($parms['where']) && $parms['where'] !== '') {
            $query .= " WHERE {$parms['where']}";
            self::processValues($values, $parms['where_values']);
        }
        
        // add group by clause if set
        if (isset($parms['group_by']) && $parms['group_by'] !== '') {
            $query .= " GROUP BY {$parms['group_by']}";
            self::processValues($values, $parms['group_by_values']);
        }
        
        // add order by clause if set
        if (isset($parms['order_by']) && $parms['order_by'] !== '') {
            $query .= " ORDER BY {$parms['order_by']}";
            self::processValues($values, $parms['order_by_values']);
        }
        
        // build limit clause if page and page_size are set
        if (isset($parms['page']) && $parms['page'] !== '' && isset($parms['page_size']) && $parms['page_size'] !== '') {
            // page number expected to be human readable i.e 1, 2, 3, ...
            $page_index = $parms['page'] < 1 ? 0 : ($parms['page'] - 1);
            $offset = $page_index * $parms['page_size'];
            $parms['limit'] = '?, ?';
            $parms['limit_values'] = array($offset, $parms['page_size']);
        }
        
        // add limit clause if set
        if (isset($parms['limit']) && $parms['limit'] !== '') {
            // make sure parms limit values is an array
            if (!is_array($parms['limit_values'])) {
                $parms['limit_values'] = array($parms['limit_values']);
            }
            
            // get the number of values received ie offset vs offset, count
            $limit_values_count = sizeof($parms['limit_values']);
            if ($limit_values_count === 1) {
                $limit = (int)$parms['limit_values'][0];
                $parms['limit'] = (string)$limit;
            } elseif ($limit_values_count === 2) {
                $offset = (int)$parms['limit_values'][0];
                $limit = (int)$parms['limit_values'][1];
                $parms['limit'] = (string)$offset . ', ' . (string)$limit;
            }
            
            // build into the query
            $query .= " LIMIT {$parms['limit']}";
        }
    }
    
    // push values onto array
    protected static function processValues(&$values, $parm_values)
    {
        // check if there are parm values to push into values
        if (!empty($parm_values)) {
            // make sure parm values are an array
            if (!is_array($parm_values)) {
                $parm_values = array($parm_values);
            }
            $values = array_merge($values, $parm_values);
        }
    }
}
