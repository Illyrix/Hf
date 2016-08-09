<?php

namespace core\system;
/**
 * ---------------------------------------------------
 * HERE IS ONE PART OF OPEN SOURCE PROJECT Illyrix/Hf.
 *      IT'S RELEASED UNDER APACHE LICENSE 2.0
 * SEE https://github.com/Illyrix/Hf FOR MORE DETAILS.
 * ---------------------------------------------------
 *
 * Class Log
 * @package core\system
 * @author Illyrix
 * @license	http://www.apache.org/licenses/LICENSE-2.0
 * The class for logging error or running information.
 */
class Log
{
    const LEVEL_ERROR   = 1;

    const LEVEL_NOTICE  = 2;

    const LEVEL_WARNING = 3;

    const LEVEL_DEBUG   = 4;

    const LEVEL_INFO    = 5;

    const LEVEL_ALL     = 6;

    static protected $_instance = null;

    static protected $_init = false;

    protected $_log_path = APP_PATH . '/runtime/log';

    protected $_log_level;

    protected $_log_filename;

    protected $_log_ext = 'log';

    static public function writeLog($level, $message) {
        (!is_null(self::$_instance)) or self::$_instance = new Log();
        return self::$_instance->createLog($level, $message);
    }

    static public function isInit() {
        return self::$_init;
    }

    protected function __construct() {
        //Init configure
        Config::readConfig();

        /*
         * Read configs and set properties.
         * If config initialize failed, will use
         * default setting.
         */
        $this->_log_path = rtrim((Config::isInit() and !is_null(Config::getConfig('LOG_PATH'))) ?
            Config::getConfig('LOG_PATH') : $this->_log_path, '/');

        $this->_log_filename = trim((Config::isInit() and !is_null(Config::getConfig('LOG_FILENAME'))) ?
            Config::getConfig('LOG_FILENAME') : date('Y-m-d'));

        $this->_log_ext = trim((Config::isInit() and !is_null(Config::getConfig('LOG_EXTENSION'))) ?
            Config::getConfig('LOG_EXTENSION') : $this->_log_ext);

        /*
         * Check if path for saving logs exits,
         * if not then try to create it.
         */
        file_exists($this->_log_path) or @mkdir($this->_log_path, 0755, true);

        if (!is_dir($this->_log_path) || !is_writable($this->_log_path)) {
            self::$_init = false;
            return;
        }

        self::$_init = true;
    }

    protected function createLog($level, $message) {
        if (!self::$_init) return false;

        //Get the full path of written file.
        $real_file = "{$this->_log_path}/{$this->_log_filename}.{$this->_log_ext}";

        //Check if the file exists. For changing
        //its permission.
        $file_exists = file_exists($real_file);

        if (!$fp = @fopen($real_file, 'ab'))
            return false;

        //If the file is new, change its permission
        //to 644.
        if (!$file_exists)
            @chmod($real_file, 0644);

        $time = microtime(true);
        $time = date('Y-m-d H:i:s') . substr(sprintf('%.6f', $time - intval($time)), 1);
        switch ($level) {
            case self::LEVEL_INFO:
                $level_output = 'Info   ';
                break;
            case self::LEVEL_NOTICE:
                $level_output = 'Notice ';
                break;
            case self::LEVEL_WARNING:
                $level_output = 'Warning';
                break;
            case self::LEVEL_DEBUG:
                $level_output = 'Debug  ';
                break;
            case self::LEVEL_ERROR:
                $level_output = 'Error  ';
                break;
            case self::LEVEL_ALL:
                $level_output = 'All    ';
                break;
            default:
                $level_output = 'User Define:' . $level;
        }
        $output = "[{$level_output}]:{$time}->{$message}" . PHP_EOL;

        /*
         * Notice: we can NOT just use fwrite() without
         * any checking its return value.
         *
         * --------------------------------------------
         * See http://php.net/manual/zh/function.fwrite.php
         * for more information
         */
        $written = false;
        for ($i = 0, $length = strlen($output); $i < $length; $i += $written) {
            if (($written = fwrite($fp, substr($output, $i))) === false)
                break;
        }

        fclose($fp);
        return ($written === false) ? false : true;
    }
}