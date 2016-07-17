<?php
define('ROOT', dirname(__DIR__));
require_once ROOT . '/vendor/autoload.php';
require_once ROOT . '/src/config.php';

spl_autoload_register(function ($class_name) {
	$class_name = str_ireplace('\\', '/', $class_name);
    include_once ROOT . '/src/' . $class_name . '.php';
});