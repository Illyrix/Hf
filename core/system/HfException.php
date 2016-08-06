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
 * Class HfException
 * @package core\system
 * @author Illyrix
 * @license	http://www.apache.org/licenses/LICENSE-2.0
 * The class to deal with every exceptions including
 * errors.
 */
class HfException extends \Exception
{
    const ERROR_LEVEL_NOTICE = 1;
    const ERROR_LEVEL_WARNING = 2;
    const ERROR_LEVEL_ERROR = 3;

    /*
     * Save the error level. NOTICE and WARNING
     * won't stop the process, ERROR will.
     */
    protected $level;

    public function __construct($message, $code = null, $level = self::ERROR_LEVEL_ERROR) {
        parent::__construct($message, $code);
        $this->level = $level;
    }

    public function displayError($status_code = 500) {

        //Check if errors showed be shown.
        $init_set = !in_array(strtolower(strval(ini_get('display_errors'))),
            Array('0', 'off', 'false', 'null', 'none', 'no'));

        /*
         * Set $error_type to a correct name of error.
         * $display saves whether this error would be
         * displayed on screen.
         * -------------------------------------------
         * NOTICE:If log file permission error
         */
        switch ($this->level) {

            case self::ERROR_LEVEL_NOTICE:
                $error_type = 'Notice';
                if (!Log::writeLog(Log::LEVEL_NOTICE, $this->message)) {
                    $this->unExceptedError("Write log failed. Please check permission.");
                    return false;
                }
                $display = $init_set && Config::getConfig('NOTICE_DISPLAY_ON');
                break;

            case self::ERROR_LEVEL_WARNING:
                $error_type = 'Warning';
                if (!Log::writeLog(Log::LEVEL_WARNING, $this->message)) {
                    $this->unExceptedError("Write log failed. Please check permission.");
                    return false;
                }
                $display = $init_set && Config::getConfig('WARNING_DISPLAY_ON');
                break;

            case self::ERROR_LEVEL_ERROR:
                $error_type = 'Error';
                if (!Log::writeLog(Log::LEVEL_ERROR, $this->message)) {
                    $this->unExceptedError("Write log failed. Please check permission.");
                    return false;
                }
                $display = $init_set && Config::getConfig('ERROR_DISPLAY_ON');
                break;
            default:
                $error_type = 'Undefined';
                if (!Log::writeLog(Log::LEVEL_ALL, $this->message)) {
                    $this->unExceptedError("Write log failed. Please check permission.");
                    return false;
                }
                $display = $init_set && Config::getConfig('ERROR_DISPLAY_ON');
                break;
        }
        /*
         * The display data shown with template.
         */
        $data['error_type'] = $error_type;
        $data['message'] = $this->message;
        $data['code'] = $this->code;

        //Bool, control display or not.
        $data['display'] = $display;

        if (is_null($data['code'])) unset($data['code']);

        /*
         * Notice: This config is WITHOUT extension.
         */
        $show_page = Config::getConfig('ERROR_DISPLAY_TEMPLATE');


        if (is_null($show_page)) {
            $show_page = CORE_PATH . '/static/error_default.html';
            $full_name = $show_page;
        } else {
            $page_ext = Config::getConfig('ERROR_DISPLAY_EXTENSION');
            $page_ext = (is_null($page_ext)) ? 'html' : $page_ext;
            $full_name = trim($show_page . '.' . $page_ext, '/');
        }

        /*
         * Visit 'app/view', 'app', root path, 'core',
         * real path one by one to load template file.
         * -------------------------------------------
         * SUGGESTION: Put template into app/view.
         * Otherwise just use default value.
         */
        if (!file_exists($template = APP_PATH . '/view/' . $full_name))
            if (!file_exists($template = APP_PATH . '/' . $full_name))
                if (!file_exists($template = ROOT_PATH . '/' . $full_name))
                    if (!file_exists($template = CORE_PATH . '/' . $full_name))
                        if (!file_exists($template = $full_name)) {
                            $this->unExceptedError("Error template file is missing.");
                            Log::writeLog(Log::LEVEL_ERROR, "Error template file is missing at " . $full_name);
                            return false;
                        }

        /*
         * There are 2 ways to show page:
         *      1.  eval('?>'.file_get_content($file));
         *      2.  ob_start();
         *          include($file);
         *          $output = ob_get_contents();
         *          ob_end_clean;
         *          echo $output;
         * -------------------------------------------
         * Using eval() looks easier, but considering
         * some server bans this function (because it's
         * unsafe in generally speaking), we choose to use
         * ob_get to gather output.
         */
        http_response_code($status_code);
        ob_start();
        include($template);
        $output = ob_get_contents();
        ob_end_clean();
        echo $output;

        return true;
    }

    //If failed to load template file or
    //log file permission denied, use it
    //for returning error information
    protected function unExceptedError($message) {
        http_response_code(503);
        echo "<p>$message</p>";
        echo "<p>{$this->message}</p>";
    }
}