<?php

namespace core\system;

use core\module\database;

class Model
{
    /**
     * @var database\Database
     */
    protected $_db;

    public function __construct($table_name = '') {
        $db_driver = is_null($type = Config::getConfig('DB_TYPE')) ? 'mysql' : $type;

        if (empty($table_name) and (($class = get_class($this)) != __CLASS__)) {
            if ( ($pos = strpos($class, __CLASS__)) !== false)
                $table_name = substr($class, 0, $pos);
            else
                $table_name = $class;
        }
        $table_name = strtolower($table_name);

        switch (strtolower($db_driver)) {
            case 'mysql':
                $this->_db = new database\Mysql_db($table_name);
                break;
            default:
                throw_exception("Other database type is NOT supported now.");
        }
    }

    public function __call($name, $arg) {
        if (method_exists($this->_db, $name) and is_callable(Array($this->_db, $name))) {
            return call_user_func_array(Array($this->_db, $name), $arg);
        } else {
            throw_exception("Try to call an undefined method {$name} in " . __CLASS__);
            exit;
        }
    }
}