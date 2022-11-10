<?php

ini_set('error_reporting', E_ALL);
ini_set('display_errors', true);
ini_set('display_startup_errors', true);
ini_set('html_errors', true);



define('APP_ROOT_PATH', __DIR__);



require_once APP_ROOT_PATH . '/Autoload/Initializer.php';



use Tests\ContainerUnitTest;

(new ContainerUnitTest())->all();
