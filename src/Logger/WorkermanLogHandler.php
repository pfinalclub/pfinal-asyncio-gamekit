<?php

namespace PfinalClub\AsyncioGamekit\Logger;

use Workerman\Worker;

/**
 * Workerman 日志处理器
 * 将日志输出到 Workerman 的日志系统
 */
class WorkermanLogHandler implements LogHandlerInterface
{
    /**
     * 是否包含上下文信息
     */
    private bool $includeContext;

    public function __construct(bool $includeContext = true)
    {
        $this->includeContext = $includeContext;
    }

    public function handle(array $record): void
    {
        $level = strtoupper($record['level']);
        $message = $record['message'];

        // 格式化消息
        $logMessage = "[{$level}] {$message}";

        // 如果需要，添加上下文信息
        if ($this->includeContext && !empty($record['context'])) {
            $context = json_encode($record['context'], JSON_UNESCAPED_UNICODE);
            $logMessage .= " Context: {$context}";
        }

        // 使用 Workerman 的日志方法
        Worker::log($logMessage);
    }

    /**
     * 配置 Workerman 日志文件
     * 
     * @param string $logFile 日志文件路径
     */
    public static function configureWorkerman(string $logFile): void
    {
        Worker::$logFile = $logFile;
    }

    /**
     * 配置 Workerman 标准输出
     * 
     * @param string $stdoutFile 标准输出文件路径
     */
    public static function configureStdout(string $stdoutFile): void
    {
        Worker::$stdoutFile = $stdoutFile;
    }

    /**
     * 设置是否以守护进程模式运行
     * 
     * @param bool $daemonize 是否守护进程
     */
    public static function setDaemonize(bool $daemonize): void
    {
        Worker::$daemonize = $daemonize;
    }
}

