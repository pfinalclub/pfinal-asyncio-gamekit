<?php

declare(strict_types=1);
namespace PfinalClub\AsyncioGamekit\Logger;

/**
 * 日志级别
 */
class LogLevel
{
    public const DEBUG = 'debug';
    public const INFO = 'info';
    public const NOTICE = 'notice';
    public const WARNING = 'warning';
    public const ERROR = 'error';
    public const CRITICAL = 'critical';
    public const ALERT = 'alert';
    public const EMERGENCY = 'emergency';

    /**
     * 日志级别权重（用于过滤）
     */
    private const LEVELS = [
        self::DEBUG => 100,
        self::INFO => 200,
        self::NOTICE => 250,
        self::WARNING => 300,
        self::ERROR => 400,
        self::CRITICAL => 500,
        self::ALERT => 550,
        self::EMERGENCY => 600,
    ];

    /**
     * 获取日志级别权重
     */
    public static function getWeight(string $level): int
    {
        return self::LEVELS[$level] ?? 0;
    }

    /**
     * 判断级别是否应该记录
     */
    public static function shouldLog(string $currentLevel, string $minLevel): bool
    {
        return self::getWeight($currentLevel) >= self::getWeight($minLevel);
    }

    /**
     * 获取所有级别
     */
    public static function getAllLevels(): array
    {
        return array_keys(self::LEVELS);
    }
}

