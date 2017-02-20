<?php

namespace core\module\database;

use core\system\Config;
use core\system\HfException;

/**
 * @author Illyrix
 * Class Model
 * Implements database options.
 */
class Mysql_db extends Database{
    protected $name;        //table name
    protected $fields = Array();      //save all columns
    protected $pk = 'id';   //primary key
    protected $sql;         //current sql
    protected $option = Array();      //query condition
    protected $optdata = Array();     //query data
    protected $dbConnect;   //current database connection
    protected $queryField;  //current column

    protected $last_sql;    //the last query sql

    protected $exp = array( //database query condition
        'eq' => '=', 'neq' => '<>', 'gt' => '>', 'egt' => '>=', 'lt' => '<', 'elt' => '<=', 'notlike' => 'NOT LIKE',
        'like' => 'LIKE', 'in' => 'IN', 'notin' => 'NOT IN', 'not in' => 'NOT IN', 'between' => 'BETWEEN',
        'not between' => 'NOT BETWEEN', 'notbetween' => 'NOT BETWEEN'
    );

    protected $conditions = Array(
        'order' => Array('key' => 'ORDER BY', 'data' => ''),
        'limit' => Array('key' => 'LIMIT', 'data' => '')
    );      //save conditions of "order by" and "limit" etc.

    /**
     * @param $modelName
     * @throws HfException
     * initialize model with model name
     */
    public function __construct($modelName) {
        $dbName = Config::getConfig('DB_NAME');
        $dbUser = Config::getConfig('DB_USER');
        $dbPwd = Config::getConfig('DB_PWD');
        $dbServer = Config::getConfig('DB_HOST');
        $dbCharSet = (is_null(Config::getConfig('DB_CHARSET'))) ? 'utf8' : (Config::getConfig('DB_CHARSET'));
        $this->dbConnect = new \PDO('mysql:host=' . $dbServer . ';dbname=' . $dbName, $dbUser, $dbPwd);
        $this->dbConnect->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
        $this->dbConnect->query("SET NAMES " . $dbCharSet);
        //check if table really exists
        if (($res = $this->dbConnect->query('SHOW TABLES')) != false) {
            $flag = false;
            foreach ($res->fetchAll() as $i)
                if ($i[0] === $modelName)
                    $flag = true;
            if (!$flag)
                throw new HfException('Table doesn\'t exist.');
        } else {
            throw new HfException('Connect database failed.');
        }
        $this->name = $modelName;
        //get all fields and save them
        if (($res = $this->dbConnect->query('SHOW COLUMNS FROM ' . $modelName)) != false) {
            $resultSet = $res->fetchAll();
            foreach ($resultSet as $i)
                array_push($this->fields, $i[0]);
        }

    }

    /**
     * @param $pk string
     * @throws HfException
     * set primary key
     * ---------------------------------------
     * NOTICE: NOT getting it in constructor()
     * is because current user may not have
     * permission to query in INFORMATION_SCHEMA
     * table.
     */
    public function setPrimaryKey($pk) {
        if (is_string($pk))
            $this->pk = $pk;
        else
            throw new HfException('Param should be string.');
    }

    /**
     * @param $sql string
     * @return mixed
     * execute the query directly
     */
    public function query($sql) {
        return $this->dbConnect->query($sql);
    }

    /**
     * @param $field string|array
     * set the query fields
     * @return $this
     * @throws HfException
     */
    public function field($field) {
        $this->queryField = '';
        if (is_array($field))
            foreach ($field as $i) {
                if (!empty($this->queryField)) $this->queryField .= ',';
                $this->queryField .= $i;
            }
        elseif (is_string($field))
            $this->queryField = $field;
        else
            throw new HfException('Param should be a string or an array.');
        return $this;
        /*
         * By returning $this, we can use it as
         * $model->where($data)->select();
         */
    }

    /**
     * execute sql
     * $relation, relation of each condition
     * $force , get all result even they has been soft deleted
     * $fetch control the type of returning result
     * @param $relation string
     * @param $force bool
     * @param $fetch int
     * relation in ('AND','OR')
     * @return array
     * @throws HfException
     */
    public function select($relation = 'AND', $force = false, $fetch = \PDO::FETCH_BOTH) {
        foreach ($this->option as $i) {
            if (!empty($this->sql)) $this->sql .= $relation . ' ';
            $this->sql .= $i;
        }
        //default to chose all
        if ($this->sql == "")
            $this->sql = "1";

        $this->sql = 'SELECT ' . (is_null($this->queryField) ? '*' : $this->queryField) . ' FROM ' . $this->name .
            ' WHERE ' . $this->sql;

        //remove data which was soft deleted
        if (in_array('deletetime', $this->fields))
            if (!$force)
                $this->sql .= ' AND deletetime IS NULL';

        //add conditions
        foreach($this->conditions as $data){
            if ($data['data'] != '')
                $this->sql .= ' '.$data['key'].' '.$data['data'];
        }

        $sql = $this->dbConnect->prepare($this->sql);
        if ($sql === false)
            throw new HfException('Query failed. Please check the fields are valid.');
        $sql->execute($this->optdata);
        $result = $sql->fetchAll($fetch);

        $this->last_sql = Array($this->sql, $this->optdata); //set last sql

        $this->option = $this->optdata = Array();       //reset option,optdata,conditions and sql
        foreach($this->conditions as $key => $data){
            $this->conditions[$key]['data'] = '';
        }
        $this->sql = '';

        return $result;
    }

    /**
     * @param $param array
     * @param $relation string
     * @return $this
     * @throws HfException
     * parameters is array, merge it into $option
     * relation in ('AND','OR')
     */
    public function where($param, $relation = 'AND') {
        if (is_array($param)) {
            $option = '';
            foreach ($param as $k => $i) {
                if (!empty($option)) $option .= $relation . ' ';      //relation between conditions
                if (is_array($i))
                    if (isset($this->exp[$i[0]])) {         //if input is like ['id'=>['lt',5]]
                        $option .= $k . ' ' . $this->exp[$i[0]] . ' ? ';
                        array_push($this->optdata, $i[1]);
                    } else {                                  //if input is like ['id'=>['<',5]]
                        $option .= $k . ' ' . $i[0] . ' ? ';
                        array_push($this->optdata, $i[1]);
                    }
                else {                                      //if input is like ['id'=>5]
                    $option .= $k . ' = ? ';
                    array_push($this->optdata, $i);
                }
            }
            array_push($this->option, '(' . $option . ') ');
        } else
            throw new HfException('Param should be an array.');
        return $this;
    }

    /**
     * @param $param array
     * @return bool|int|string return id if success,
     * and "0" if no self-increment, else return false
     * @throws HfException
     * insert a new record
     */
    public function insert($param) {
        if (!is_array($param))
            throw new HfException('Param should be an array.');
        $insertData = Array();
        $insertPlacehold = '';
        $insertFields = '';
        foreach ($param as $k => $i)
            if (in_array($k, $this->fields)) {
                array_push($insertData, $i);
                if (!empty($insertFields)) $insertFields .= ',';
                $insertFields .= $k;
                if (!empty($insertPlacehold)) $insertPlacehold .= ',';
                $insertPlacehold .= '?';
            }
        $sql = 'INSERT INTO ' . $this->name . ' (' . $insertFields . ') VALUES (' . $insertPlacehold . ')';
        $query = $this->dbConnect->prepare($sql);
        if ($query === false)
            throw new HfException('Query failed. Please check the fields are valid.');
        $result = $query->execute($insertData);
        $this->last_sql = Array($sql, $insertData);  //update the last sql
        if ($result)
            return ($this->dbConnect->lastInsertId());
        else
            return false;
    }

    /**
     * @param $force bool should update deletetime ? default to yes
     * @throws HfException
     * @return int number of delete cows
     * delete records
     */
    public function delete($force = false) {
        foreach ($this->option as $i) {
            if (!empty($this->sql)) $this->sql .= 'AND ';
            $this->sql .= $i;
        }

        //add conditions
        foreach($this->conditions as $data){
            if ($data['data'] != '')
                $this->sql .= ' '.$data['key'].' '.$data['data'];
        }

        if ($force or (in_array('deletetime', $this->fields))) {
            $this->sql = 'DELETE FROM ' . $this->name .
                ' WHERE ' . $this->sql;
            $sql = $this->dbConnect->prepare($this->sql);
            if ($sql === false)
                throw new HfException('Query failed. Please check the fields are valid.');
            $sql->execute($this->optdata);
            $result = $sql->rowCount();

            $this->last_sql = Array($this->sql, $this->optdata); //set last sql

            $this->option = Array();       //reset option,optdata,conditions and sql
            $this->optdata = Array();
            foreach($this->conditions as $key => $data){
                $this->conditions[$key]['data'] = '';
            }
            $this->sql = '';
        } else {
            //update deletetime
            $this->sql = 'UPDATE ' . $this->name . ' SET deletetime = now() WHERE ' . $this->sql;
            $sql = $this->dbConnect->prepare($this->sql);
            if ($sql === false)
                throw new HfException('Query failed. Please check the fields are valid.');
            $sql->execute($this->optdata);
            $result = $sql->fetch()[0];

            $this->last_sql = Array($this->sql, $this->optdata); //set last query

            $this->option = Array();       //reset option,optdata,conditions and sql
            $this->optdata = Array();
            foreach($this->conditions as $key => $data){
                $this->conditions[$key]['data'] = '';
            }
            $this->sql = '';
        }

        return $result;
    }

    /**
     * count rows
     * @param $force bool should we query all records, include records have been soft deleted.
     * @throws HfException
     * @return int
     */
    public function count($force = false) {
        foreach ($this->option as $i) {
            if (!empty($this->sql)) $this->sql .= 'AND ';
            $this->sql .= $i;
        }

        $this->sql = 'SELECT COUNT(*) FROM ' . $this->name .
            ' WHERE ' . $this->sql;

        //remove soft delete records
        if (in_array('deletetime', $this->fields))
            if (!$force)
                $this->sql .= ' AND deletetime IS NULL';

        $sql = $this->dbConnect->prepare($this->sql);
        if ($sql === false)
            throw new HfException('Query failed. Please check the fields are valid.');
        $sql->execute($this->optdata);
        $result = $sql->fetch();

        $this->last_sql = Array($this->sql, $this->optdata); //set last query

        $this->option = Array();       //reset option,optdata and sql
        $this->optdata = Array();
        $this->sql = '';

        return $result[0];
    }

    /**
     * @param $param
     * @throws HfException
     * @return bool
     * update some record
     */
    public function update($param) {
        if (!is_array($param))
            throw new HfException('Param should be an array.');
        if (!isset($param[$this->pk]))
            throw new HfException('Update function needs a primary key in param.');

        $insertData = Array();
        $insertFields = '';
        foreach ($param as $k => $i)
            if (in_array($k, $this->fields)) {
                array_push($insertData, $i);
                if (!empty($insertFields)) $insertFields .= ',';
                $insertFields .= $k . ' = ? ';
            }
        array_push($insertData, $param[$this->pk]);
        $sql = 'UPDATE ' . $this->name . ' SET ' . $insertFields . ' WHERE ' . $this->pk . ' = ?';

        //add conditions
        foreach($this->conditions as $data){
            if ($data['data'] != '')
                $this->sql .= ' '.$data['key'].' '.$data['data'];
        }

        $query = $this->dbConnect->prepare($sql);
        if ($query === false)
            throw new HfException('Query failed. Please check the fields are valid.');
        $result = $query->execute($insertData);

        $this->last_sql = Array($sql, $insertData);  //update last sql
        //reset conditions and sql
        foreach($this->conditions as $key => $data){
            $this->conditions[$key]['data'] = '';
        }
        return $result;

    }

    /**
     * @return array
     * return last sql
     */
    public function getLastQuery() {
        return $this->last_sql;
    }

    /**
     * @param $start int
     * @param $end null|int
     * @return Mysql_db
     */
    public function limit($start, $end = null) {
        $start = intval($start);
        if (is_null($end))
            $this->conditions['limit']['data'] = (string)$start;
        else {
            $end = intval($end);
            $this->conditions['limit']['data'] = $start . ',' . $end;
        }
        return $this;
    }

    /**
     * @param string|array $order
     * @return Mysql_db
     */
    public function order($order){
        //TODO:add filter
        if (is_array($order)) {
            foreach ($order as $i) {
                if (is_string($i))
                    if ($this->conditions['order']['data'] == '')
                        $this->conditions['order']['data'] = $i;
                    else
                        $this->conditions['order']['data'] .= ',' . $i;
            }
        }
        else{
            $order = (string) $order;
            $this->conditions['order']['data'] = $order;
        }
        return $this;

    }

    /**
     * @return bool
     */
    public function beginTransaction() {
        return $this->dbConnect->beginTransaction();
    }

    /**
     * @return bool
     */
    public function commit() {
        return $this->dbConnect->commit();
    }

    /**
     * @return bool
     */
    public function rollBack() {
        return $this->dbConnect->rollBack();
    }

    /**
     * @return bool
     */
    public function inTransaction() {
        return $this->dbConnect->inTransaction();
    }
}