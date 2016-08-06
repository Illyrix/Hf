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
 * @license	http://www.apache.org/licenses/LICENSE-2.0
 * The class for implementing router.
 */
class Route
{
    /*
     * Here define some constants to represent
     * some methods of HTTP.
     */
    const ROUTE_GET    = 0b0001;
    const ROUTE_POST   = 0b0010;
    const ROUTE_PUT    = 0b0100;
    const ROUTE_DELETE = 0b1000;

    /**
     * @var array
     * Save all routes we defined in other place.
     * It contains an array of
     */
    static protected $_routes = Array();

    /**
     * @var string
     * The class we should to initialize for
     * current request.
     */
    static protected $_class = '';

    /**
     * @var string
     * The method url matched, it will be called
     * in main framework, if it's callable.
     */
    static protected $_method = '';

    /**
     * @var array
     * The arguments we get from url, and will be
     * added into $_GET/$_POST after filter in
     * other place(not here).
     */
    static protected $_arguments = Array();

    /*
     * Use this method to add a route record. The
     * route should be like 'id/{id}'. $opt save
     * which function or method you want to call.
     * Method determines which HTTP method the
     * route follows.
     */
    static public function route($route, $opt, $method = self::ROUTE_GET) {
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
            (isset($opt[1])) or exit("Error input value");      //TODO:Here should throw Exception
        } elseif (!($opt instanceof \Closure)) {
            //TODO:Here throw Exception
        }
        
        //Every route is saved as an array of 3.
        array_push(self::$_routes, array($route, $opt, $method));
    }

    static public function parseUrl($url) {

    }

    static public function generateUrl($param) {

    }

    /**
     * Route constructor.
     */
    public function __construct() {
        
    }

    public function parse() {

    }
}