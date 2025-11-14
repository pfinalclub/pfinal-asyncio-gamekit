<?php

declare(strict_types=1);

namespace PfinalClub\AsyncioGamekit\Exceptions;

/**
 * 配置异常
 * 
 * 处理配置相关的错误
 */
class ConfigException extends GameException
{
    /**
     * 配置文件未找到
     */
    public static function fileNotFound(string $filePath): self
    {
        return new self(
            "Configuration file not found: {$filePath}",
            1001,
            null,
            ['file_path' => $filePath]
        );
    }

    /**
     * 配置格式无效
     */
    public static function invalidFormat(string $filePath, string $reason = ''): self
    {
        $message = "Invalid configuration format in file: {$filePath}";
        if ($reason) {
            $message .= " - {$reason}";
        }
        
        return new self(
            $message,
            1002,
            null,
            ['file_path' => $filePath, 'reason' => $reason]
        );
    }

    /**
     * 配置验证失败
     */
    public static function validationFailed(string $field, string $reason): self
    {
        return new self(
            "Configuration validation failed for field '{$field}': {$reason}",
            1003,
            null,
            ['field' => $field, 'reason' => $reason]
        );
    }
}

