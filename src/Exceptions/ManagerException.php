<?php

declare(strict_types=1);

namespace PfinalClub\AsyncioGamekit\Exceptions;

/**
 * 管理器异常
 * 
 * 处理RoomManager等管理器相关的错误
 */
class ManagerException extends GameException
{
    /**
     * 超过最大限制
     */
    public static function maxLimitReached(string $resource, int $limit): self
    {
        return new self(
            "Max {$resource} limit ({$limit}) reached",
            2001,
            null,
            ['resource' => $resource, 'limit' => $limit]
        );
    }

    /**
     * 资源已存在
     */
    public static function resourceAlreadyExists(string $resourceType, string $resourceId): self
    {
        return new self(
            "{$resourceType} {$resourceId} already exists",
            2002,
            null,
            ['resource_type' => $resourceType, 'resource_id' => $resourceId]
        );
    }

    /**
     * 无效的参数
     */
    public static function invalidArgument(string $argument, string $reason): self
    {
        return new self(
            "Invalid argument '{$argument}': {$reason}",
            2003,
            null,
            ['argument' => $argument, 'reason' => $reason]
        );
    }

    /**
     * 操作失败
     */
    public static function operationFailed(string $operation, string $reason): self
    {
        return new self(
            "Operation '{$operation}' failed: {$reason}",
            2004,
            null,
            ['operation' => $operation, 'reason' => $reason]
        );
    }
}

