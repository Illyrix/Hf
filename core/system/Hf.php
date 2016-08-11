<?php
/**
 * ---------------------------------------------------
 * HERE IS ONE PART OF OPEN SOURCE PROJECT Illyrix/Hf.
 *      IT'S RELEASED UNDER APACHE LICENSE 2.0
 * SEE https://github.com/Illyrix/Hf FOR MORE DETAILS.
 * ---------------------------------------------------
 *
 * @author Illyrix
 * @license    http://www.apache.org/licenses/LICENSE-2.0
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


//Set timezone. The default value is Asia/Shanghai.
is_null(Config::getConfig('DEFAULT_TIMEZONE')) or date_default_timezone_set(Config::getConfig('DEFAULT_TIMEZONE'));

is_null(Config::getConfig('DEFAULT_CHARSET')) or ini_set('default_charset', Config::getConfig('DEFAULT_CHARSET'));

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


/*
 * Load custom functions, default directory is
 * /app/common.
 * -------------------------------------------
 * SUGGESTION: Add checking if function exists
 * code before define it.
 */
load_custom_script(is_null(Config::getConfig('CUSTOM_FUNCTION_DIRECTORY')) ? APP_PATH . '/common' :
    Config::getConfig('CUSTOM_FUNCTION_DIRECTORY'), false);


//Give a default value to PATH_INFO.
(isset($_SERVER['PATH_INFO'])) or $_SERVER['PATH_INFO'] = '';


//Analysis route, default action is
// throwing 404 error.
$callable = 'return_404';

if (!Route::current()->is_success)
    $bad_route = true;
elseif (!Route::current()->is_closure) {
    //Make the 1st letter capital.
    $class = ucfirst(Route::current()->class);
    $method = Route::current()->method;

    $bad_route = !(bool)is_controller_callable($class, $method);
    /*
     * NOTICE: is_controller_callable() will
     * return an array as value if it is
     * callable, or return false.
     */
    if (!$bad_route) $callable = Array(is_controller_callable($class, $method)[0], $method);

} else {
    $bad_route = false;
    $callable = Route::current()->callable;
}

//The controller or method not found
if ($bad_route) {

    //Try to redirect the page was set.
    if (Config::getConfig('NOT_FOUND_REDIRECT')) {
        if (Config::getConfig('NOT_FOUND_CONTROLLER')) {
            $class = Config::getConfig('NOT_FOUND_CONTROLLER');
            $method =
                is_null(Config::getConfig('NOT_FOUND_METHOD')) ? 'index' : Config::getConfig('NOT_FOUND_METHOD');
            $bad_route = !(bool)is_controller_callable($class, $method);
            if (!$bad_route) $callable = Array(is_controller_callable($class, $method)[0], $method);
        } else
            throw_exception("Direct when 404 occurs is set, but missing corresponding controller config.");
    }
}

//If failed to redirect.
if ($bad_route) {
    throw_exception("404 Not Found", null, 404);
    exit;
}

//Call the method or closure function
//the route returned.
if (!($callable instanceof \Closure) && isset($callable[0])) $callable[0] = new $callable[0];
call_user_func($callable);