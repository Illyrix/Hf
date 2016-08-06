<?php
/**
 * @author Illyrix
 * Include universal functions
 */
/**
 * ---------------------------------------------------
 * HERE IS ONE PART OF OPEN SOURCE PROJECT Illyrix/Hf.
 *      IT'S RELEASED UNDER APACHE LICENSE 2.0
 * SEE https://github.com/Illyrix/Hf FOR MORE DETAILS.
 * ---------------------------------------------------
 *
 * @author Illyrix
 * @license	http://www.apache.org/licenses/LICENSE-2.0
 * Include universal functions
 */

/*
 * redirect to the url and exit.
 */
if (!function_exists('redirect')) {
    /**
     * @param $url
     * @param $time
     */
    function redirect($url, $time = 0) {
        //$url = APP_PATH . $url;
        if (!headers_sent()) {
            if ($time == 0) {
                header('Location: ' . $url);
            } else {
                header("refresh:{$time};url={$url}");
            }
            exit();
        } else {
            $str = "<meta http-equiv='Refresh' content='{$time};URL={$url}'>";
            exit($str);
        }
    }
}

/*
 * return 404.
 */
if (!function_exists('return_404')) {
    /**
     * @return bool
     */
    function return_404() {
        if (!headers_sent()) {
            header('HTTP/1.1 404 Not Found');
            header("status: 404 Not Found");
            return true;
        } else
            return false;       //http head has been sent
    }
}

/*
 * Set session with param. Cannot unset session with
 * dimension > 2. Support using '.' divides 2 dimensions
 * of an array type session.
 */
if (!function_exists('set_session')) {
    /**
     * @param $field string '' means all of session
     * @param $data mixed if null means delete this session
     * @return bool
     */
    function set_session($field, $data = null) {

        (session_status() !== PHP_SESSION_DISABLED) or session_start();

        //Set or delete all sessions
        if ($field == '')
            if (is_null($data)) {
                $_SESSION = Array();
                return true;
            } else if (is_array($data)) {
                $_SESSION = $data;
                return true;
            } else
                return false;

        if (strpos($field, '.')) {
            $fields = explode('.', $field);

            //Find current key
            $temp = &$_SESSION;

            //Try to unset
            $parent = Array();
            foreach ($fields as $i) {
                if (!isset($temp[$i]))
                    $temp[$i] = Array();
                $parent['p'] = &$temp;
                $parent['key'] = $i;
                $temp = &$temp[$i];
            }

            if (!is_null($data)) $temp = $data;
            else
                unset($parent['p'][$parent['key']]);
        } else
            if (!is_null($data)) $_SESSION[$field] = $data;
            else unset($_SESSION[$field]);
        return true;
    }
}

/*
 * Get session and if key is undefined return null.
 */
if (!function_exists('get_session')) {
    /**
     * @param $field
     * @return null|mixed
     */
    function get_session($field) {

        (session_status() !== PHP_SESSION_DISABLED) or session_start();

        if ($field == '')
            return $_SESSION;

        $fields = explode('.', $field);
        $temp = &$_SESSION;
        foreach ($fields as $i) {

            if (!isset($temp[$i]))
                return null;
            $temp = &$temp[$i];
        }
        return $temp;
    }
}

/*
 * The handler function to show errors on screen.
 *
 */
if (!function_exists('handler_error')) {
    /**
     * @param int $severity
     * @param string $message
     * @param string $filepath
     * @param string|int $line
     */
    function handler_error($severity, $message, $filepath, $line) {
        /*
         * If this error should be ignored.
         */
        if ((error_reporting() & $severity) !== $severity) return;

        $e = new \core\system\HfException($message . ' in ' . $filepath . ' on line ' . $line);
        $e->displayError(500);

        if (in_array($severity, Array(E_ERROR, E_COMPILE_ERROR, E_CORE_ERROR, E_USER_ERROR)))
            exit();
    }
}

if (!function_exists('handler_exception')) {
    /**
     * @param Exception $exception
     */
    function handler_exception($exception) {
        /*
         * Uncaught exception we make it an error
         * and log it, display it if need.
         */
        $e = new \core\system\HfException($exception->getMessage() . ' in ' .
            $exception->getFile() . ' on line ' . $exception->getLine());
        $e->displayError(500);
    }
}

if (!function_exists('handler_shutdown')) {
    function handler_shutdown() {
        //Find the last error, and display it.
        $last_e = error_get_last();
        if (is_null($last_e))
            return;
        else {
            handler_error($last_e['type'], $last_e['message'], $last_e['file'], $last_e['line']);
        }
    }
}

/*
 * Autoload class function. Try to load class by its
 * name and namespace.
 */
if (!function_exists('autoload_class')) {
    /**
     * @param string $classname
     */
    function autoload_class($classname) {
        $routes = explode('\\', $classname);
        $filename = ROOT_PATH;
        foreach ($routes as $i)
            $filename .= '/' . $i;
        if (is_file($filename . '.php'))
            include_once($filename . '.php');
        elseif (is_file($filename . '.class.php'))
            include_once($filename . '.class.php');
    }
}

/*
 * Here return an array of full name of files.
 * $recursive represents shall we traversal the dir
 * recursively or not.
 * -----------------------------------------------
 * If $recursive was set not, the array returned will
 * contents dirs under $dir.
 */
if (!function_exists('map_dir_file')) {
    /**
     * @param string $dir
     * @param bool $recursive
     * @return array
     */
    function map_dir_file($dir, $recursive = true) {
        $result = Array();
        foreach (glob($dir . '/*') as $s) {
            if (is_dir($s) && $recursive)
                $result = array_merge($result, map_dir_file($s, true));
            else
                array_push($result, $s);
        }
        return $result;
    }
}

if (!function_exists('throw_exception')) {
    /**
     * @param string $message
     * @param null|int $code
     * @param int $level
     */
    function throw_exception($message, $code = null,$status_code = 500, $level = \core\system\HfException::ERROR_LEVEL_ERROR) {
        $e = new \core\system\HfException($message, $code, $level);
        $e->displayError($status_code);
    }
}