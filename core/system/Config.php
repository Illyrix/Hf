<?php

namespace core\system;

use core;

/**
 * @author Illyrix
 * Class Config
 * read config file
 */
class Config
{

    /**
     * Save all configures
     * @var array
     */
    static protected $configure;

    /**
     * We need know whether the configures are really
     * read into $configure. This will be useful for us
     * to solve the problem that error handler function
     * needs to read config but in function readConfig
     * occurs fatal error. In the worst case, it will
     * cause endless loop.
     * @var bool
     */
    static protected $init = false;

    //Define the default config file path,
    //relative to CORE_PATH
    const CONFIG_DEFAULT_PATH = CORE_PATH . '/static/config.default.php';


    const CONFIG_CUSTOM_PATH  = APP_PATH . '/config';

    protected function __construct() {
    }

    static public function getConfig($field) {
        (self::$init) or self::readConfig();
        return isset(self::$configure[$field]) ? (self::$configure[$field]) : null;
    }

    static public function setConfig($field, $value) {
        (self::$init) or self::readConfig();
        self::$configure[$field] = $value;
    }

    /*
     * Return true if configures were correctly read.
     */
    static public function isInit() {
        return self::$init;
    }

    static public function readConfig() {
        if (self::$init)
            return;
        //Ensure configures are correctly saved.
        if (!self::readDefault() or !self::readCustom())
            return;

        //If all configures were read, set flag true.
        self::$init = true;
    }

    static protected function readDefault() {
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

        self::$configure = &$configure;
        return true;
    }

    static protected function readCustom() {
        $absolute_path = rtrim(self::CONFIG_CUSTOM_PATH, '/');

        //Traversal custom config path recursively.
        $files = map_dir_file($absolute_path);
        foreach ($files as $key => $file) {
            $info = pathinfo($file);

            //We just need files with extension 'php'
            if (isset($info['extension'])) {
                if (strtolower($info['extension']) == 'php') {

                    /*
                     * Same as method 'readDefault()'. If
                     * there were syntax error in file, it
                     * will call shutdown handler function.
                     */
                    $configure = require($file);
                    if (!is_array($configure)) {
                        http_response_code(503);
                        echo("The config file needs an array as return at {$file}");
                        exit();
                    }else
                        self::$configure = array_merge(self::$configure, $configure);
                }
            }else

                //If is a dir not a file
                continue;
        }
        return true;
    }
}