<?php
/**
 * ---------------------------------------------------
 * HERE IS ONE PART OF OPEN SOURCE PROJECT Illyrix/Hf.
 *      IT'S RELEASED UNDER APACHE LICENSE 2.0
 * SEE https://github.com/Illyrix/Hf FOR MORE DETAILS.
 * ---------------------------------------------------
 *
 * @author Illyrix
 * @license	http://www.apache.org/licenses/LICENSE-2.0
 * The file to load each parts.
 */

namespace core\system;
/*
 * Check if it is required by index.php
 */
defined('CORE_PATH') or exit("Access file required");

/*
 * Define current version
 */
defined('HF_VERSION') or define('HF_VERSION', '0.0.0');

/*
 * Load functions from core/function.php
 *
 * DO NOT PUT USER FUNCTIONS IN core/function.php
 * Instead of it, you can put functions in
 * app/common/*.php.
 */
if (file_exists(CORE_PATH . '/function.php'))
    require_once CORE_PATH . '/function.php';
else
    exit("Missing function file : " . CORE_PATH . '/function.php');

/*
 * Define handler to solve error or exceptions. The
 * functions are in core/function.php
 */
set_error_handler('handler_error');
set_exception_handler('handler_exception');
register_shutdown_function('handler_shutdown');

/*
 * Here register autoload function if class missed.
 * It will NOT conflict with vendor/autoload.php.
 *
 * Ensure your namespace start from ROOT_PATH
 */
spl_autoload_register('autoload_class', true, true);


/*
 * Add composer support. You can disable composer 
 * in config file with setting COMPOSER_LOAD false.
 *
 */
if (Config::getConfig('COMPOSER_LOAD') == true) {
    if (file_exists(ROOT_PATH . '/vendor/autoload.php'))
        require_once ROOT_PATH . '/vendor/autoload.php';
    else
        Log::writeLog(Log::LEVEL_ERROR, "COMPOSER_LOAD has set to true, but missing autoload.php");
}

//Set timezone. The default value is Asia/Shanghai.
is_null(Config::getConfig('DEFAULT_TIMEZONE')) or date_default_timezone_set(Config::getConfig('DEFAULT_TIMEZONE'));

throw new \Exception('hahaha');