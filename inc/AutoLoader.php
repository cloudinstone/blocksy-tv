<?php

/**
 * Auto Loader for WordPress
 * 
 * @author DressPress
 * @link https://dresspress.org
 * @version 0.1.2
 */

namespace WPTVTheme;

final class AutoLoader {
    private $namespace;
    private $filepath;

    public function __construct($namespace, $filepath) {
        $this->namespace = $namespace;
        $this->filepath = $filepath;

        spl_autoload_register(array($this, 'callback'));
    }

    public function callback($class) {
        $namespace = $this->namespace;

        if (strpos($class, $namespace) !== 0) {
            return;
        }

        $class = substr($class, strlen($namespace . DIRECTORY_SEPARATOR));
        // DO NOT USE `str_replace` as the class name maybe include the namespace.
        // $class = str_replace($namespace . DIRECTORY_SEPARATOR, '', $class);

        $class = str_replace('\\', DIRECTORY_SEPARATOR, $class);

        $file = untrailingslashit($this->filepath) . DIRECTORY_SEPARATOR .  $class . '.php';

        if (file_exists($file)) {
            require_once($file);
        }
    }
}
