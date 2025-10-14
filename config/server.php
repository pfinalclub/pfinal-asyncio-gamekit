<?php
/**
 * 游戏服务器配置
 */

return [
    // 服务器基本配置
    'host' => '0.0.0.0',
    'port' => 2345,
    
    // Worker 配置
    'worker' => [
        'name' => 'GameServer',
        'count' => 4,  // Worker 进程数，建议设置为 CPU 核心数
        'protocol' => 'websocket',
    ],
    
    // Workerman 配置
    'workerman' => [
        'daemonize' => false,  // 是否守护进程模式
        'log_file' => __DIR__ . '/../logs/workerman.log',
        'stdout_file' => __DIR__ . '/../logs/stdout.log',
        'pid_file' => __DIR__ . '/../runtime/gameserver.pid',
    ],
];

