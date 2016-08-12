<?php

namespace core\module\database;

abstract class Database {
    abstract public function query($sql);
    
    abstract public function field($field);
    
    abstract public function select($relation);
    
    abstract public function where($param);
    
    abstract public function insert($param);
    
    abstract public function delete();
    
    abstract public function count();
    
    abstract public function update($param);
    
    abstract public function getLastQuery();

    abstract public function limit($start);

    abstract public function order($order);
}