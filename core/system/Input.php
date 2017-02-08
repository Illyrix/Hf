<?php

namespace core\system;


class Input
{
    protected $_arguments;

    public function __construct() {
        $this->_key_filter();
    }

    protected function _key_filter() {
        /* Merge $_GET and arguments from url.
         * Not using array_merge is because
         * we want to avoid numeric key renumbered.
         * See http://php.net/manual/en/function.array-merge.php.
         */
        list($this->_arguments['GET'], $this->_arguments['POST'], $this->_arguments['COOKIE']) =
            Array($_GET + Route::current()->arguments, $_POST, $_COOKIE);

        //Filtering keys of each pair of input
        //values. Eg. $_GET['<'] = '<' will be
        //escaped.
        $filter = is_null(Config::getConfig('INPUT_KEY_FILTER')) ? 'htmlspecialchars' :
            Config::getConfig('INPUT_KEY_FILTER');
        $param = is_null(Config::getConfig('INPUT_KEY_FILTER_PARAM')) ? Array(ENT_QUOTES) :
            Config::getConfig('INPUT_KEY_FILTER_PARAM');

        $temp = Array();
        if (!empty($filter)) {
            if (is_callable($filter))
                foreach ($this->_arguments as $j => $v) {
                    foreach ($v as $k => $i) {
                        array_unshift($param, $k);
                        $temp[call_user_func_array($filter, $param)] = $i;
                        array_shift($param);
                    }
                    $this->_arguments[$j] = $temp;
                    $temp = Array();
                }
            else throw_exception("Key filter function is NOT callable");
        }
    }

    /**
     * @param string $field
     * @param mixed|null $default
     * @param string $filter
     * @return mixed
     */
    public function input($field = '', $default = null, $filter = '', $param = Array()) {
        $arguments = $this->_arguments['COOKIE'] + $this->_arguments['POST'] + $this->_arguments['GET'];
        return $this->_filter($arguments, $field, $filter, $param, $default);
    }

    /**
     * @param string $field
     * @param mixed|null $default
     * @param string $filter
     * @return mixed
     */
    public function get($field = '', $default = null, $filter = '', $param = Array()) {
        return $this->_filter($this->_arguments['GET'], $field, $filter, $param, $default);
    }

    /**
     * @param string $field
     * @param mixed|null $default
     * @param string $filter
     * @return mixed
     */
    public function post($field = '', $default = null, $filter = '', $param = Array()) {
        return $this->_filter($this->_arguments['POST'], $field, $filter, $param, $default);
    }

    /**
     * @param string $field
     * @param mixed|null $default
     * @param string $filter
     * @return mixed
     */
    public function cookie($field = '', $default = null, $filter = '', $param = Array()) {
        return $this->_filter($this->_arguments['COOKIE'], $field, $filter, $param, $default);
    }

    /**
     * @param array $array
     * @param string $field
     * @param callable|string $filter
     * @param mixed|null $default
     * @return mixed
     */
    protected function _filter(&$array, $field, $filter = '', $param = Array(), $default = null) {
        if (empty($filter)) {
            $filter = is_null($de_f = Config::getConfig('INPUT_VALUE_FILTER')) ? 'htmlspecialchars' : $de_f;
            $param = empty($param) ? (is_null($de_p = Config::getConfig('INPUT_VALUE_FILTER_PARAM')) ?
                Array() : $de_p) : $param;
        } elseif ($filter == Config::getConfig('INPUT_VALUE_FILTER_PARAM')) {
            $param = empty($param) ? (is_null($de_p = Config::getConfig('INPUT_VALUE_FILTER_PARAM')) ?
                Array() : $de_p) : $param;
        } else {
            $param = empty($param) ? Array() : $param;
        }

        if ($field)
            if (!empty($filter)) {
                if (!is_callable($filter)) throw_exception("The filter function is NOT callable.");
                {
                    if (isset($array[$field])) {
                        array_unshift($param, $array[$field]);
                        return call_user_func_array($filter, $param);
                    }else
                     return $default;
                }
            } else
                return (isset($array[$field]) ? $array[$field] : $default);
        else
            if (!empty($filter)) {
                if (!is_callable($filter)) throw_exception("The filter function is NOT callable.");
                $result = Array();
                foreach ($array as $k => $v) {
                    array_unshift($param, $v);
                    $result[$k] = call_user_func_array($filter, $param);
                    array_shift($param);
                }
                return $result;
            } else
                return $array;
    }
}