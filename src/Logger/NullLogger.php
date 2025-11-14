<?php

declare(strict_types=1);
namespace PfinalClub\AsyncioGamekit\Logger;

/**
 * 空日志器（不记录任何日志）
 * 用于在未提供日志器时的默认实现
 */
class NullLogger implements LoggerInterface
{
    public function debug(string $message, array $context = []): void
    {
        // Do nothing
    }

    public function info(string $message, array $context = []): void
    {
        // Do nothing
    }

    public function notice(string $message, array $context = []): void
    {
        // Do nothing
    }

    public function warning(string $message, array $context = []): void
    {
        // Do nothing
    }

    public function error(string $message, array $context = []): void
    {
        // Do nothing
    }

    public function critical(string $message, array $context = []): void
    {
        // Do nothing
    }

    public function alert(string $message, array $context = []): void
    {
        // Do nothing
    }

    public function emergency(string $message, array $context = []): void
    {
        // Do nothing
    }
}

