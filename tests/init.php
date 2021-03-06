<?php
error_reporting(E_ALL);
ini_set('display_errors', 'on');

/**
 * Prepares a simple autoloader for the Capirussa\Eobot namespace
 */

date_default_timezone_set('Europe/Amsterdam');

// handle autoloading
spl_autoload_register(
    function ($className) {
        if ($className === 'MockHttpRequest') {
            require_once(dirname(__FILE__) . '/Capirussa/Http/mock/MockHttpRequest.php');
        } else if (preg_match('/^Capirussa\\\\Http/', $className)) {
            $filePath = dirname(__FILE__) . '/../' . str_replace('\\', DIRECTORY_SEPARATOR, $className) . '.php';
            if (file_exists($filePath)) {
                require_once($filePath);
            }
        }
    }
);
