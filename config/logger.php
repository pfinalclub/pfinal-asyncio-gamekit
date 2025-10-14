<?php
/**
 * 日志配置文件
 */

use PfinalClub\AsyncioGamekit\Logger\LogLevel;

return [
    // 最小日志级别
    'min_level' => LogLevel::INFO,
    
    // 控制台日志配置
    'console' => [
        'enabled' => true,
        'color' => true,  // 启用彩色输出
    ],
    
    // 文件日志配置
    'file' => [
        'enabled' => false,  // 默认关闭，生产环境建议开启
        'path' => __DIR__ . '/../logs/game.log',
        'max_size' => 10 * 1024 * 1024,  // 10MB
        'max_files' => 5,  // 保留5个日志文件
    ],
];

