<?php

namespace PfinalClub\AsyncioGamekit\Logger;

/**
 * 控制台日志处理器
 */
class ConsoleLogHandler implements LogHandlerInterface
{
    private bool $colorEnabled;

    private const COLORS = [
        LogLevel::DEBUG => "\033[0;37m",     // 白色
        LogLevel::INFO => "\033[0;32m",      // 绿色
        LogLevel::NOTICE => "\033[0;36m",    // 青色
        LogLevel::WARNING => "\033[0;33m",   // 黄色
        LogLevel::ERROR => "\033[0;31m",     // 红色
        LogLevel::CRITICAL => "\033[1;31m",  // 加粗红色
        LogLevel::ALERT => "\033[1;35m",     // 加粗紫色
        LogLevel::EMERGENCY => "\033[1;41m", // 红色背景
    ];

    private const RESET = "\033[0m";

    public function __construct(bool $colorEnabled = true)
    {
        $this->colorEnabled = $colorEnabled && $this->supportsColor();
    }

    public function handle(array $record): void
    {
        $level = strtoupper($record['level']);
        $datetime = $record['datetime'];
        $message = $record['message'];

        if ($this->colorEnabled) {
            $color = self::COLORS[$record['level']] ?? '';
            $output = sprintf(
                "%s[%s]%s [%s] %s\n",
                $color,
                $datetime,
                self::RESET,
                $level,
                $message
            );
        } else {
            $output = sprintf(
                "[%s] [%s] %s\n",
                $datetime,
                $level,
                $message
            );
        }

        // 输出到标准输出或标准错误
        if (in_array($record['level'], [LogLevel::ERROR, LogLevel::CRITICAL, LogLevel::ALERT, LogLevel::EMERGENCY])) {
            fwrite(STDERR, $output);
        } else {
            echo $output;
        }
    }

    /**
     * 检查终端是否支持颜色
     */
    private function supportsColor(): bool
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            // Windows 10+ 支持 ANSI 颜色
            return getenv('ANSICON') !== false 
                || getenv('ConEmuANSI') === 'ON' 
                || getenv('TERM') === 'xterm';
        }

        return function_exists('posix_isatty') && posix_isatty(STDOUT);
    }
}

