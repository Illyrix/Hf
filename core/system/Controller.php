<?php

namespace core\system;


class Controller
{
    protected $_view;

    protected $_input;

    public function __construct() {
        $this->_input = new Input();
        $this->_view = new View(array_pop(explode('\\', get_class($this))));
        $pre_action = is_null($pre = Config::getConfig('PRE_ACTION')) ? '_preAction' : $pre;

        if (method_exists($this, $pre_action)) call_user_func(Array($this, $pre_action));
    }

    public function __destruct() {
        $post_action = is_null($post = Config::getConfig('POST_ACTION')) ? '_postAction' : $post;

        if (method_exists($this, $post_action)) call_user_func(Array($this, $post_action));
    }

    public function __get($name) {
        $real_name = '_' . strtolower($name);
        if (in_array($real_name, Array('_view', '_input')))
            return $this->$real_name;
        else throw_exception("Try to get undefined property: {$name} in " . __CLASS__);
        return null;
    }
}