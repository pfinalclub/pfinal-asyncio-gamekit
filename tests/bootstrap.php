<?php

/**
 * PHPUnit 测试引导文件
 * 配置测试环境，禁用不必要的日志输出
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PfinalClub\AsyncioGamekit\Logger\LoggerFactory;
use PfinalClub\AsyncioGamekit\Logger\NullLogger;

// 在测试环境中禁用控制台日志输出，减少测试时的日志噪音
LoggerFactory::configure([
    'console.enabled' => false,
    'file.enabled' => false,
    'min_level' => 'emergency', // 只记录紧急错误
]);
