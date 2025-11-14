<?php

declare(strict_types=1);
namespace PfinalClub\AsyncioGamekit\Logger;

/**
 * 日志记录器
 */
class Logger implements LoggerInterface
{
    private string $minLevel;
    private array $handlers = [];
    private array $context = [];

    public function __construct(string $minLevel = LogLevel::INFO, array $handlers = [])
    {
        $this->minLevel = $minLevel;
        $this->handlers = $handlers;

        // 如果没有指定处理器，使用默认的控制台处理器
        if (empty($this->handlers)) {
            $this->handlers[] = new ConsoleLogHandler();
        }
    }

    /**
     * 设置全局上下文
     */
    public function setContext(array $context): void
    {
        $this->context = $context;
    }

    /**
     * 添加全局上下文
     */
    public function addContext(string $key, mixed $value): void
    {
        $this->context[$key] = $value;
    }

    /**
     * 添加日志处理器
     */
    public function addHandler(LogHandlerInterface $handler): void
    {
        $this->handlers[] = $handler;
    }

    /**
     * 设置最小日志级别
     */
    public function setMinLevel(string $level): void
    {
        $this->minLevel = $level;
    }

    /**
     * 记录日志
     */
    public function log(string $level, string $message, array $context = []): void
    {
        // 检查日志级别
        if (!LogLevel::shouldLog($level, $this->minLevel)) {
            return;
        }

        // 合并全局上下文和本次上下文
        $context = array_merge($this->context, $context);

        // 格式化消息
        $message = $this->interpolate($message, $context);

        // 创建日志记录
        $record = [
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'timestamp' => microtime(true),
            'datetime' => date('Y-m-d H:i:s'),
        ];

        // 调用所有处理器
        foreach ($this->handlers as $handler) {
            $handler->handle($record);
        }
    }

    /**
     * 调试日志
     */
    public function debug(string $message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    /**
     * 信息日志
     */
    public function info(string $message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    /**
     * 通知日志
     */
    public function notice(string $message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    /**
     * 警告日志
     */
    public function warning(string $message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    /**
     * 错误日志
     */
    public function error(string $message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    /**
     * 严重错误日志
     */
    public function critical(string $message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    /**
     * 警报日志
     */
    public function alert(string $message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    /**
     * 紧急日志
     */
    public function emergency(string $message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    /**
     * 插值替换消息中的占位符
     */
    private function interpolate(string $message, array $context): string
    {
        $replace = [];
        foreach ($context as $key => $val) {
            if (is_scalar($val) || (is_object($val) && method_exists($val, '__toString'))) {
                $replace['{' . $key . '}'] = $val;
            }
        }
        return strtr($message, $replace);
    }
}

