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

    static protected $instance = null;

    static protected $init = false;

    protected $logPath = APP_PATH . '/runtime/log';

    protected $logLevel;

    protected $logFilename;

    protected $logExt = 'log';

    static public function writeLog($level, $message) {
        (!is_null(self::$instance)) or self::$instance = new Log();
        return self::$instance->createLog($level, $message);
    }

    static public function isInit() {
        return self::$init;
    }

    protected function __construct() {
        //Init configure
        Config::readConfig();

        /*
         * Read configs and set properties.
         * If config initialize failed, will use
         * default setting.
         */
        $this->logPath = rtrim((Config::isInit() and !is_null(Config::getConfig('LOG_PATH'))) ?
            Config::getConfig('LOG_PATH') : $this->logPath, '/');

        $this->logFilename = trim((Config::isInit() and !is_null(Config::getConfig('LOG_FILENAME'))) ?
            Config::getConfig('LOG_FILENAME') : date('Y-m-d'));

        $this->logExt = trim((Config::isInit() and !is_null(Config::getConfig('LOG_EXTENSION'))) ?
            Config::getConfig('LOG_EXTENSION') : $this->logExt);

        /*
         * Check if path for saving logs exits,
         * if not then try to create it.
         */
        file_exists($this->logPath) or @mkdir($this->logPath, 0755, true);

        if (!is_dir($this->logPath) || !is_writable($this->logPath)) {
            self::$init = false;
            return;
        }

        self::$init = true;
    }

    protected function createLog($level, $message) {
        if (!self::$init) return false;

        //Get the full path of written file.
        $real_file = "{$this->logPath}/{$this->logFilename}.{$this->logExt}";

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