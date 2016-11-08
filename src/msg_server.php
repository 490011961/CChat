<?php
use JWebIM\MsgServerManager;
require __DIR__ . '/Autoloader.php';

spl_autoload_register([
    'Autoloader',
    'load'
]);

try {
	MsgServerManager::start();
} catch (Exception $e) {
    echo $e->getMessage() . PHP_EOL;
}
