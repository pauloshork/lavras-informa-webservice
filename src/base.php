<?php
define('ROOT', dirname(__DIR__));
define('SRC', ROOT . '/src/');

require_once ROOT . '/vendor/autoload.php';
require_once SRC . '/config.php';

spl_autoload_register(function ($class_name) {
	$class_name = str_ireplace('\\', '/', $class_name);
    return @include SRC . $class_name . '.php';
});