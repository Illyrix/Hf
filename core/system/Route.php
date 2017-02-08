<?php

namespace core\system;

/**
 * ---------------------------------------------------
 * HERE IS ONE PART OF OPEN SOURCE PROJECT Illyrix/Hf.
 *      IT'S RELEASED UNDER APACHE LICENSE 2.0
 * SEE https://github.com/Illyrix/Hf FOR MORE DETAILS.
 * ---------------------------------------------------
 *
 * Class Route
 * @package core\system
 * @author Illyrix
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 * The class for implementing router.
 */
class Route
{
    /*
     * Here define some constants to represent
     * some methods of HTTP.
     */
    const ROUTE_GET = 0b0001;
    const ROUTE_POST = 0b0010;
    const ROUTE_PUT = 0b0100;
    const ROUTE_DELETE = 0b1000;

    /**
     * @var array
     * Save all routes we defined in other place.
     * It contains an array of
     */
    static protected $_routes = Array();

    /**
     * @var array
     * Save all param filters. The same rule as
     * regular expression.
     * Eg. 'w+','.*'
     */
    static protected $_filters = Array();

    /**
     * @var bool
     * The flag represents if we have read all
     * routes from custom files.
     */
    static protected $_init = false;

    protected $_default_controller = 'Index';

    protected $_default_method = 'index';

    protected $_default_suffix = 'html';

    /**
     * @var bool
     * If the route was parsed successfully.
     */
    protected $_is_success = false;

    /**
     * @var string
     * The class we should to initialize for
     * current request.
     */
    protected $_class = '';

    /**
     * @var string
     * The method url matched, it will be called
     * in main framework, if it's callable.
     */
    protected $_method = '';

    /**
     * @var null|callable
     * The value is not null while a Closure
     * object bind this route.
     */
    protected $_callable = null;

    /**
     * @var null|bool
     * If the route is parsed successfully, the
     * value will be set as if is a Closure
     * object bind this route.
     */
    protected $_is_closure = null;

    /**
     * @var array
     * The arguments we get from url, and will be
     * added into $_GET/$_POST after filter in
     * other place(not here).
     */
    protected $_arguments = Array();


    /**
     * @var string
     * The url will be parsed.
     * NOTICE: Every time parseUrl() called will
     * change its value.
     */
    protected $_url = '';


    /**
     * @var null|Route
     * This var is for parsing route of current
     * request, in other words, you can create
     * other instances for parsing other urls.
     */
    static protected $_instance = null;

    /*
     * In constructor, read routes setting
     * and configures.
     */
    public function __construct() {
        self::$_init or self::readRoute();
        self::$_init = true;
        $this->_default_controller = is_null(Config::getConfig('DEFAULT_ROUTE_CONTROLLER')) ?
            $this->_default_controller : Config::getConfig('DEFAULT_ROUTE_CONTROLLER');

        $this->_default_method = is_null(Config::getConfig('DEFAULT_ROUTE_METHOD')) ?
            $this->_default_method : Config::getConfig('DEFAULT_ROUTE_METHOD');

        $this->_default_suffix = is_null(Config::getConfig('DEFAULT_ROUTE_SUFFIX')) ?
            $this->_default_suffix : Config::getConfig('DEFAULT_ROUTE_SUFFIX');
    }

    /*
     * By this way, we can use $this->url to
     * get $this->_url's value.
     */
    public function __get($name) {
        if (in_array($name, Array('url', 'class', 'method', 'arguments', 'is_success', 'is_closure', 'callable'))) {
            $name = '_' . $name;
            return $this->$name;
        } else
            throw new HfException("Try to get undefined property {$name} of " . __CLASS__);
    }

    /*
     * Use this method to add a route record. The
     * route should be like 'id/{id}'. $opt save
     * which function or method you want to call.
     * Method determines which HTTP method the
     * route follows.
     */
    static public function route($route, $opt, $method = self::ROUTE_GET | self::ROUTE_POST) {
        //We'd better remove useless char at both
        // sides of $route.
        $route = explode('/', trim($route, "/ \t\n\r\0\x0B"));

        /*
         * Here we accept two ways of $opt:
         *      1. Class name + '@' + method name.
         *          Eg. 'Index@index'
         *      2. Closure function
         *          Eg. function(){echo '404';}
         * Case 1: we will catch class and method
         *      name, save them as an array.
         * Case 2: we just save it as a Closure
         *      object.
         */
        if (is_string($opt)) {
            $opt = explode('@', $opt, 2);
            (isset($opt[1])) or throw_exception("Param of route syntax error");
        } elseif (!($opt instanceof \Closure)) {
            throw_exception("Param of route is not callable");
        }

        //Every route is saved as an array of 3.
        array_push(self::$_routes, array($route, $opt, $method));
    }

    static public function filter($param, $filter) {
        (is_string($param) && is_string($filter)) or throw_exception('Setting filter needs 2 strings as param');
        self::$_filters[$param] = $filter;
    }

    /**
     * @return Route
     * It will return the instance of global
     * environment route object.
     */
    static public function current() {

        if (is_null(self::$_instance)) {
            self::$_instance = new Route();

            /* TODO:It's better to move the analysis of
             * URL mode to a new class.
             */
            self::$_instance->parseUrl(trim($_SERVER['PATH_INFO'], '/')) or Config::getConfig('DISABLE_DEFAULT_ROUTE')
                or self::$_instance->defaultUrl(trim($_SERVER['PATH_INFO'], '/'));
        }

        return self::$_instance;
    }

    /*
     * Read custom config files from
     * app/route(default)
     */
    static function readRoute() {
        $dir =
            is_null(Config::getConfig('ROUTE_DIRECTORY')) ? APP_PATH . '/route' : Config::getConfig('ROUTE_DIRECTORY');
        if (!is_dir($dir)) throw_exception("The route config directory is missing");
        return load_custom_script($dir, false);
    }

    /*
     * Map all routes to find the matching
     * one. If so, return an array of
     * class, method and arguments, and
     * set corresponding flag. Otherwise
     * return false.
     */
    public function parseUrl($url) {

        $this->_url = $url;

        $route = explode('/', $url);
        foreach (self::$_routes as $r) {

            //If HTTP method not match.
            if (!(request_method() & $r[2])) continue;

            //Match the empty url string
            if ($r[0] == Array(0 => ''))
                if (trim($url) === '') {
                    if ($r[1] instanceof \Closure) {
                        $this->_callable = $r[1];
                        $this->_is_closure = $this->_is_success = true;
                    } else {
                        list($this->_class, $this->_method) = $r[1];
                        list($this->_is_success, $this->_is_closure) = Array(true, false);
                    }
                    return Array($r[1], Array());
                }
                else
                    continue;


            if (count($r[0]) != count($route))
                continue;

            $args = Array();
            $flag = true;
            foreach ($r[0] as $key => $param) {
                $preg = preg_quote($param);
                $pos = 0;
                $unique = Array();
                while (1) {
                    $posl = strpos($preg, "\{", $pos);
                    if ($posl === false)
                        break;
                    $pos = $posl;
                    $posr = strpos($preg, "\}", $pos);
                    if ($posl === false)
                        break;
                    $pos = $posr;
                    $replace = substr($preg, $posl + 2, $posr - $posl - 2);

                    //2 param represent string in one route，eg. /{time}-{time}/
                    if (isset($unique[$replace])) {
                        throw_exception("Duplicate represent string:{$replace}", null,
                            500, HfException::ERROR_LEVEL_WARNING);
                        continue;
                    }

                    $unique[$replace] = true;
                    $preg =
                        str_replace("\{" . $replace . "\}", '(?<' . $replace . '>' . (isset(self::$_filters[$replace]) ?
                                self::$_filters[$replace] : "\w+") . ')', $preg);
                }

                //Check if case sensitive.
                $preg = '/' . $preg . ((Config::getConfig('ROUTE_CASE_SENS')) ? "/" : "/i");
                $matches = Array();
                if (!preg_match($preg, $route[$key], $matches)) {
                    $flag = false;
                    break;
                } else {
                    foreach ($unique as $k => $i)
                        $args[$k] = $matches[$k];
                }
            }
            if ($flag) {
                if ($r[1] instanceof \Closure) {
                    list($this->_callable, $this->_arguments) = Array($r[1], $args);
                    $this->_is_closure = $this->_is_success = true;
                } else {
                    list($this->_class, $this->_method) = $r[1];
                    list($this->_is_success, $this->_is_closure) = Array(true, false);
                    $this->_arguments = $args;
                }
                return Array($r[1], $args);
            }
        }
        $this->_is_success = false;
        return false;
    }

    /*
     * If no matching routes, it will use
     * default url parse mode. It follows:
     * /ClassName/MethodName/1stParamKey/1stParamValue/2nd****.Suffix
     */
    public function defaultUrl($url) {
        $this->_url = $url;

        //Remove suffix
        $suffix = '.' . $this->_default_suffix;
        if (substr($url, -strlen($suffix), strlen($suffix)) == $suffix)
            $url = substr($url, 0, strlen($url) - strlen($suffix));

        $route = explode('/', $url);


        $class = empty($route[0]) ? $this->_default_controller : $route[0];
        $method = empty($route[1]) ? $this->_default_method : $route[1];
        $arguments = Array();
        $i = 2;
        while (isset($route[$i])) {
            if (isset($route[$i + 1])) {
                $arguments[$route[$i]] = $route[$i + 1];
                $i = $i + 2;
            } else {
                $arguments[$route[$i]] = '';
                break;
            }
        }
        list($this->_is_success, $this->_is_closure) = Array(true, false);
        list($this->_class, $this->_method, $this->_arguments) = Array($class, $method, $arguments);
        return Array(Array($this->_class, $this->_method), $arguments);
    }

    public function generateUrl($param) {

    }
}