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
 * This is the access file, where all work starts.
 */

/*
 * The directory which includes your application. Default
 * value is 'app'. You can change it with a relative path
 * or a absolute path.
 *
 * DO NOT ADD TRAILING SLASH!
 */
$app_directory = 'app';

/*
 * The directory where core files are. Usually you'd better
 * do NOT change it.
 *
 * DO NOT ADD TRAILING SLASH!
 */
$core_directory = 'core';


/*
 * Set ROOT_PATH as the dir of index.php.
 *
 * NOTICE: NO TRAILING SLASH IN END
 */
defined('ROOT_PATH') or define('ROOT_PATH', __DIR__);

/*
 * Set CORE_PATH based on $core_directory, if the dir really
 * exists. Eg. it will be set as "/var/www/html/core".
 *
 * NOTICE: NO TRAILING SLASH IN END
 */
if (!defined('CORE_PATH')) {
    $core_directory = rtrim($core_directory, '/');

    // If $core_directory is a relative path
    if (($real_dir = realpath($core_directory)) !== false)
        define('CORE_PATH', $real_dir);

    // If $core_directory is a absolute path
    elseif (is_dir($core_directory))
        define('CORE_PATH', $core_directory);

    else {
        http_response_code(503);
        echo "Core directory is set incorrectly.";
        exit();
    }
}

/*
 * Define APP_PATH. Here includes all your application
 * files. Eg. "/var/www/html/app".
 *
 * NOTICE: NO TRAILING SLASH IN END
 */
if (!defined('APP_PATH')) {
    $app_directory = rtrim($app_directory, '/');

    // If $app_directory is a relative path
    if (($real_dir = realpath($app_directory)) !== false)
        define('APP_PATH', $real_dir);

    // If $app_directory is a absolute path
    elseif (is_dir($app_directory))
        define('APP_PATH', $app_directory);

    else {
        http_response_code(503);
        echo "App directory is set incorrectly.";
        exit();
    }
}

/*
 * Define system path. It's under CORE_PATH. In default case,
 * it should be "/var/www/html/core/system".
 */
defined('SYSTEM_PATH') or define('SYSTEM_PATH', realpath(CORE_PATH . '/system'));

require_once SYSTEM_PATH . '/Hf.php';