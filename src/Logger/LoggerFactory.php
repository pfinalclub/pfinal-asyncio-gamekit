<?php

namespace PfinalClub\AsyncioGamekit\Logger;

/**
 * 日志工厂类
 */
class LoggerFactory
{
    private static ?Logger $instance = null;
    private static array $config = [];

    /**
     * 配置日志系统
     */
    public static function configure(array $config): void
    {
        self::$config = $config;
        self::$instance = null; // 重置实例
    }

    /**
     * 获取日志实例
     */
    public static function getInstance(): Logger
    {
        if (self::$instance === null) {
            self::$instance = self::createLogger();
        }
        return self::$instance;
    }

    /**
     * 创建日志实例
     */
    private static function createLogger(): Logger
    {
        $minLevel = self::$config['min_level'] ?? LogLevel::INFO;
        $handlers = [];

        // 添加控制台处理器
        if (self::$config['console']['enabled'] ?? true) {
            $handlers[] = new ConsoleLogHandler(
                self::$config['console']['color'] ?? true
            );
        }

        // 添加文件处理器
        if (self::$config['file']['enabled'] ?? false) {
            $handlers[] = new FileLogHandler(
                self::$config['file']['path'] ?? 'logs/game.log',
                self::$config['file']['max_size'] ?? 10 * 1024 * 1024,
                self::$config['file']['max_files'] ?? 5
            );
        }

        return new Logger($minLevel, $handlers);
    }

    /**
     * 快捷方法：调试日志
     */
    public static function debug(string $message, array $context = []): void
    {
        self::getInstance()->debug($message, $context);
    }

    /**
     * 快捷方法：信息日志
     */
    public static function info(string $message, array $context = []): void
    {
        self::getInstance()->info($message, $context);
    }

    /**
     * 快捷方法：警告日志
     */
    public static function warning(string $message, array $context = []): void
    {
        self::getInstance()->warning($message, $context);
    }

    /**
     * 快捷方法：错误日志
     */
    public static function error(string $message, array $context = []): void
    {
        self::getInstance()->error($message, $context);
    }

    /**
     * 快捷方法：严重错误日志
     */
    public static function critical(string $message, array $context = []): void
    {
        self::getInstance()->critical($message, $context);
    }

    /**
     * 快捷方法：警报日志
     */
    public static function alert(string $message, array $context = []): void
    {
        self::getInstance()->alert($message, $context);
    }

    /**
     * 快捷方法：紧急日志
     */
    public static function emergency(string $message, array $context = []): void
    {
        self::getInstance()->emergency($message, $context);
    }

    /**
     * 快捷方法：通知日志
     */
    public static function notice(string $message, array $context = []): void
    {
        self::getInstance()->notice($message, $context);
    }
}

