<?php

namespace core\system;

use core;

/**
 * ---------------------------------------------------
 * HERE IS ONE PART OF OPEN SOURCE PROJECT Illyrix/Hf.
 *      IT'S RELEASED UNDER APACHE LICENSE 2.0
 * SEE https://github.com/Illyrix/Hf FOR MORE DETAILS.
 * ---------------------------------------------------
 *
 * Class Config
 * @package core\system
 * @author Illyrix
 * @license	http://www.apache.org/licenses/LICENSE-2.0
 * Read configures from files.
 */
class Config
{

    //Define the default config file path,
    //relative to CORE_PATH
    const CONFIG_DEFAULT_PATH = CORE_PATH . '/static/config.default.php';


    const CONFIG_CUSTOM_PATH  = APP_PATH . '/config';

    /**
     * Save all configures
     * @var array
     */
    static protected $_configure;

    /**
     * We need know whether the configures are really
     * read into $configure. This will be useful for us
     * to solve the problem that error handler function
     * needs to read config but in function readConfig
     * occurs fatal error. In the worst case, it will
     * cause endless loop.
     * @var bool
     */
    static protected $_init = false;


    protected function __construct() {
    }

    static public function getConfig($field) {
        (self::$_init) or self::readConfig();
        return isset(self::$_configure[$field]) ? (self::$_configure[$field]) : null;
    }

    static public function setConfig($field, $value) {
        (self::$_init) or self::readConfig();
        self::$_configure[$field] = $value;
    }

    /*
     * Return true if configures were correctly read.
     */
    static public function isInit() {
        return self::$_init;
    }

    static public function readConfig() {
        if (self::$_init)
            return;
        //Ensure configures are correctly saved.
        if (!self::_readDefault() or !self::_readCustom())
            return;

        //If all configures were read, set flag true.
        self::$_init = true;
    }

    static protected function _readDefault() {
        $default_file = rtrim(self::CONFIG_DEFAULT_PATH, '/');

        if (!file_exists($default_file)) {
            http_response_code(503);
            echo("The default config file is missing at core/static/config.default.php");
            exit();
        }

        /*
         * If there are syntax errors in this file, it
         * will NOT trigger error handler function, but
         * also will trigger shutdown handler function.
         *
         */
        $configure = require($default_file);
        if (!is_array($configure)) {
            http_response_code(503);
            echo("The default config file needs an array as return at core/static/config.default.php");
            exit();
        }

        self::$_configure = &$configure;
        return true;
    }

    static protected function _readCustom() {
        $absolute_path = rtrim(self::CONFIG_CUSTOM_PATH, '/');

        //Traversal custom config path recursively and
        //load them.
        $configures = load_custom_script($absolute_path);
        foreach ($configures as $configure)
            if (!is_array($configure)) {
                http_response_code(503);
                echo("The config file needs an array as return in path {$absolute_path}");
                exit();
            }else
                self::$_configure = array_merge(self::$_configure, $configure);
        return true;
    }
}