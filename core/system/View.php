<?php

namespace core\system;

require_once CORE_PATH . '/library/Twig/Autoloader.php';

class View
{
    protected $_twig;

    protected $_namespace;

    protected $_ext = '.html';

    protected $_vars = Array();

    /**
     * View constructor.
     * @param string $dir
     */
    public function __construct($dir = '') {
        $template['dir'] = is_null(Config::getConfig('APP_TEMPLATE_DIR')) ? APP_PATH . '/view/' . $dir
            : Config::getConfig('APP_TEMPLATE_DIR') . "/{$dir}";
        $template['cache'] =
            is_null(Config::getConfig('APP_TEMPLATE_CACHE_DIR')) ? APP_PATH . '/runtime/cache/view/' . $dir
                : Config::getConfig('APP_TEMPLATE_CACHE_DIR') . "/{$dir}";

        $this->_ext = is_null(Config::getConfig('APP_TEMPLATE_EXTENSION')) ? '.html'
            : '.' . Config::getConfig('APP_TEMPLATE_EXTENSION');
        $this->_ext = ($this->_ext == '.') ? '' : $this->_ext;

        \Twig_Autoloader::register();

        $this->_namespace = empty($dir) ? \Twig_Loader_Filesystem::MAIN_NAMESPACE : $dir;

        $loader = new \Twig_Loader_Filesystem();
        $loader->addPath($template['dir'], $this->_namespace);
        if (Config::getConfig('APP_TEMPLATE_CACHE_ON'))
            $twig_config = Array('cache' => $template['cache']);
        else
            $twig_config = Array();

        $this->_twig = new \Twig_Environment($loader, $twig_config);
    }

    /**
     * @param string $view_name
     */
    public function display($view_name) {
        $this->_twig->display('@' . $this->_namespace . '/' . $view_name . $this->_ext, $this->_vars);
    }

    /**
     * @param string|int|array $index
     * @param mixed $value
     */
    public function render($index, $value = null) {
        if (is_array($index))
            $this->_vars = $this->_vars + $index;
        else
            $this->_vars = $this->_vars + Array($index => $value);
    }

    /**
     * @param string $view_name
     * @return string
     */
    public function fetch($view_name) {
        return $this->_twig->render('@' . $this->_namespace . '/' . $view_name . $this->_ext, $this->_vars);
    }

    /**
     * @return \Twig_Environment
     */
    public function twig() {
        return $this->_twig;
    }
}