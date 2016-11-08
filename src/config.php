<?php
$config['server'] = array(
    // 监听的HOST
    'host' => '0.0.0.0',
    // 监听的端口
    'port' => '9503',
);

$config['swoole'] = array(
    'worker_num' => 1,
    // 不要修改这里
    'max_request' => 0,
    'task_worker_num' => 1,
    // 是否要作为守护进程
    'daemonize' => 0
);

$config['proxy'] = array(
    'host' => '127.0.0.1',
    'port' => '6379',
	'key' => 'webim_msg_servers'
);

return $config;