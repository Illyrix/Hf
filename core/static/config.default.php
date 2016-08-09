<?php
return Array(
    'ERROR_DISPLAY_TEMPLATE'  => 'core/static/error_default',
    'ERROR_DISPLAY_EXTENSION' => 'html',
    'ERROR_DISPLAY_ON'        => true,
    'NOTICE_DISPLAY_ON'       => true,
    'WARNING_DISPLAY_ON'      => true,


    'DEFAULT_TIMEZONE' => 'Asia/Shanghai',
    'DEFAULT_CHARSET'  => 'utf-8',


    'COMPOSER_LOAD' => true,


    'LOG_PATH'      => APP_PATH . '/runtime/log',
    'LOG_FILENAME'  => date('Y-m-d'),
    'LOG_EXTENSION' => 'log',

    'ROUTE_CASE_SENS'          => false,
    'DISABLE_DEFAULT_ROUTE'    => false,
    'ROUTE_DIRECTORY'          => APP_PATH . '/route',
    'DEFAULT_ROUTE_CONTROLLER' => 'Index',
    'DEFAULT_ROUTE_METHOD'     => 'index',
    'DEFAULT_ROUTE_SUFFIX'     => 'html',

    'NOT_FOUND_REDIRECT'       => false,
    'NOT_FOUND_CONTROLLER'     => '',
    'NOT_FOUND_METHOD'         => ''
);