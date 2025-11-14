<?php

declare(strict_types=1);

namespace PfinalClub\AsyncioGamekit\Exceptions;

/**
 * 权限异常
 * 
 * 处理权限和RBAC相关的错误
 */
class PermissionException extends GameException
{
    /**
     * 权限被拒绝
     */
    public static function denied(string $userId, string $permission): self
    {
        return new self(
            "Permission denied: User '{$userId}' does not have permission '{$permission}'",
            3001,
            null,
            ['user_id' => $userId, 'permission' => $permission]
        );
    }

    /**
     * 权限不存在
     */
    public static function permissionNotFound(string $permission): self
    {
        return new self(
            "Permission not found: {$permission}",
            3002,
            null,
            ['permission' => $permission]
        );
    }

    /**
     * 角色不存在
     */
    public static function roleNotFound(string $role): self
    {
        return new self(
            "Role not found: {$role}",
            3003,
            null,
            ['role' => $role]
        );
    }

    /**
     * 用户没有角色
     */
    public static function noRole(string $userId): self
    {
        return new self(
            "User '{$userId}' has no assigned roles",
            3004,
            null,
            ['user_id' => $userId]
        );
    }
}

