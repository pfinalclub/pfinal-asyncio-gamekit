<?php
/**
 * 生产环境日志配置
 */

use PfinalClub\AsyncioGamekit\Logger\LogLevel;

return [
    'min_level' => LogLevel::WARNING,  // 生产环境只记录警告及以上
    
    'console' => [
        'enabled' => false,  // 生产环境关闭控制台输出
        'color' => false,
    ],
    
    'file' => [
        'enabled' => true,  // 启用文件日志
        'path' => '/var/log/gamekit/game.log',
        'max_size' => 50 * 1024 * 1024,  // 50MB
        'max_files' => 10,
    ],
];

