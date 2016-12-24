<?php

/**
 * Autoloads classes by namespace path
 *
 * @param string $className
 */
function __autoload($className) {

    // remove heading \ from classname
    if (substr($className, 0, 1) == '\\') {
        $className = substr($className, 1);
    }

    // replace \ with /, it works both on windows and unix, path must be relative to document root that is the cwd
    $path = str_replace('\\', '/', $className) . '.php';

    // file directory
    $dir = realpath(dirname(__FILE__));

    if (file_exists($dir . '/../' . $path)) {
        include_once $dir . '/../' . $path;
    }

}

spl_autoload_register('__autoload');
