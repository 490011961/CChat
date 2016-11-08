<?php
use JWebIM\ProxyServerManager;

require __DIR__ . '/Autoloader.php';
spl_autoload_register([
    'Autoloader',
    'load'
]);

try {
    /* 从redis服务器获取消息服务器信息 */
    if (! class_exists('Redis')) {
        throw new Exception('请安装redis扩展');
    }
    
    $config = require __DIR__ . '/config.php';
    $redis = new Redis();
    $redis->connect($config['proxy']['host'], $config['proxy']['port'], 0.5);
    
    $msgServers = json_decode($redis->get($config['proxy']['key']), true);
    if (empty($msgServers)) {
        throw new Exception('消息列表配置不正确');
    }
    
    $redis->close();
    ProxyServerManager::getInstance()->run($msgServers);
} catch (Exception $e) {
    echo $e->getMessage() . PHP_EOL;
}
